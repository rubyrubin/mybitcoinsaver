<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use GuzzleHttp;
use Illuminate\Http\Request;


class SavingsSummaryController extends Controller
{
    private $validFrequencies = ['daily', 'monthly', 'weekly', 'annually'];
    // TODO implement the other currencies
    private $validCurrencies = ['USD', 'NZD', 'GBP'];
    private static $hostname;

    /**
     * @param $amount
     * @return string|null The error message
     */
    private function validateAmount($amount){
        if( !is_numeric($amount) ){
            return "Amount needs to be a numeric value";
        }
        if($amount <= 0) {
            return "Amount needs to be greater than 0";
        }
        if($amount > 100000) {
            return "We do not support savings of this size";
        }
        return null;
    }

    /**
     * @param $frequency
     * @return string|null The error message
     */
    private function validateFrequency($frequency){
        if(!in_array($frequency, $this->validFrequencies)) {
            return "Frequency must be one of: " . implode($this->validFrequencies, ',');
        }
        return null;
    }

    /**
     * @param $months
     * @return string|null The error message
     */
    private function validateMonths($months){
        $months = intval($months);
        if(!$months) {
            return "Months was not an int, could not be converted to an int, or was 0";
        }
        if($months < 0) {
            return "Months needs to be greater than 0";
        }
        Log::info("Months converted: " . $months);
        $firstDayOfBitcoin = 1230940800;
        $epochDiff = time() - $firstDayOfBitcoin;
        $numberOfSecondsIn1Month = 2592000;
        $maxMonths = floor($epochDiff / $numberOfSecondsIn1Month);

        if($months > $maxMonths) {
            return "Months cannot be greater than " . $maxMonths;
        }
        return null;
    }

    /**
     * @param $currency
     * @return string|null The error message
     */
    private function validateCurrency($currency){
        if(!in_array($currency, $this->validCurrencies)) {
            return "Currency must be one of: " . implode($this->validCurrencies, ',');
        }
        return null;
    }

    /**
     * converts a string-based frequency to a number of seconds
     *
     * @param $frequency
     * @return int seconds
     */
    private function convertFrequencyToSeconds($frequency) {
        if($frequency == 'monthly') {
            return 2629746;
        }
        if($frequency == 'annually') {
            return 2629746*12;
        }
        if($frequency == 'daily') {
            return 86400;
        }
        return 86400 * 7;
    }

    /**
     * Finds an appropriate epoch to match from the blockchain.info api, which only presents data every other day
     * starting from the inception of bitcoin
     *
     * @param $epoch
     * @return int matched epoch
     */
    private function matchEpochToData($epoch) {
        $firstDayOfBitcoin = 1230940800;
        $millisecondsTwoDays = 172800;  // the api returns values in 2 day increments
        // This is turning the epoch into the last known data point (up to two days behind)
        if( ($epoch - $firstDayOfBitcoin) % $millisecondsTwoDays > 0) {
            Log::debug("About to round the epoch to the previous known value");
            return $epoch - (($epoch - $firstDayOfBitcoin) % $millisecondsTwoDays);
        }
        return $epoch;
    }

    /**
     * Returns number of NZD in 1 USD based on the api call to api.fixer.io
     *
     * @return int number of NZD in 1 USD
     */
    private function getLatestUSDtoNZDConversion() {
        // http://api.fixer.io/latest?base=USD
        $client = new GuzzleHttp\Client();
        $res = $client->get('http://api.fixer.io/latest?base=USD');
        if($res->getStatusCode() == 200) {
            $payload = json_decode($res->getBody());
            return $payload->rates->NZD;
        } else {
            abort(400, "Could not call currency converstion endpoint");
        }
        return 1;
    }

    /**
     * @param $amount
     * @param $frequency
     * @param $months
     * @return array {epoch of investment =>
     *                  ["total_saved" => cumulative dollars saved,
     *                   "bitcoin_saved" => cumulative quantity of bitcoin saved,
     *                   "bitcoin_value" => total amount of Bitcoin owned in USD,
     *                   "savings_gain" => bitcoin_value/total_saved]}
     */
    private function createDatesAndAmounts($amount, $frequency, $months) {
        // starting at the beginning, buy the bitcoin and then buy again each increment
        // convert the frequency to epochs
        // first go back the number of months
        $startingEpoch = time() - ($months * 2629746);
        $datesAndAmounts = array();
        $convertedFrequency = $this->convertFrequencyToSeconds($frequency);
        $client = new GuzzleHttp\Client();
        $maxTime = time() - (86400 * 2);    // go back 2 days, since they only generate data every 2 days

        $res = $client->get(self::$hostname.'/api/market_prices/many/from=' . $startingEpoch . '&to=' . $maxTime);
        if($res->getStatusCode() == 200) {
            $allValuesJson = (string)$res->getBody();
        } else {
            abort(400, "Could not call internal route to get bitcoin prices");
        }
        $allValues = json_decode($allValuesJson);
        $totalSaved = 0;
        $bitcoinTotalOwned = 0;
        $savingsGain = 0.0;
        $bitcoinSaved = 0.0;
        $bitcoinValue = 0.0;
        $nzdToUsd = $this->getLatestUSDtoNZDConversion();
        Log::info("Current value of NZD in USD is: " . $nzdToUsd);
        for ($i = $startingEpoch; $i <= $maxTime; $i=$i+$convertedFrequency) {
            $payload = array();
            Log::debug("trying to find epoch: " . $i);
            $epoch = $this->matchEpochToData($i);
            $epochValues = array_column($allValues, 'epoch');
            $keyFound = array_search($epoch, $epochValues);
            // convert to NZD. TODO support other currency conversions here
            $bitcoinPrice = $allValues[$keyFound]->value/$nzdToUsd;
            if ($bitcoinPrice > 0) {
                Log::debug("bitcoin price found: " . $bitcoinPrice);
                $currentBitcoinSaved = $amount / $bitcoinPrice;
                $bitcoinSaved = $bitcoinSaved + $currentBitcoinSaved;
                $bitcoinTotalOwned = $bitcoinTotalOwned + $currentBitcoinSaved;
                $bitcoinValue = $bitcoinTotalOwned * $bitcoinPrice;
                $totalSaved = $totalSaved + $amount;
                $savingsGain = $bitcoinValue / $totalSaved;
                Log::debug("amount: " . $amount . " bitcoin_saved: " . $bitcoinSaved . " bitCoin value: " . $bitcoinValue. " total saved: " .$totalSaved . "savings gain: " . $savingsGain );

            }
            $payload['bitcoin_saved'] = $bitcoinSaved;
            $payload['total_saved'] = $totalSaved;
            $payload['bitcoin_value'] = $bitcoinValue;
            $payload['savings_gain'] = $savingsGain;
            $datesAndAmounts[$i] = $payload;
        }
        return $datesAndAmounts;
    }

    /**
     * Main entry point for calculating the savings summary for the chart
     * 
     * @param Request $request
     * @param $amount
     * @param $frequency
     * @param $months
     * @param $currency
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function calculate(Request $request, $amount, $frequency, $months, $currency){
        self::$hostname = $request->getHttpHost();
        Log::info("amount: " . $amount . " frequency: " . $frequency . " months: " . $months . " currency: " . $currency);
        if($error_message = $this->validateAmount($amount)){
            return response()->json(['error' => $error_message], 422);
        }
        $frequency = strtolower($frequency);
        if($error_message = $this->validateFrequency($frequency)){
            return response()->json(['error' => $error_message], 422);
        }
        if($error_message = $this->validateMonths($months)){
            return response()->json(['error' => $error_message], 422);
        }
        $currency = strtoupper($currency);
        if($error_message = $this->validateCurrency($currency)){
            return response()->json(['error' => $error_message], 422);
        }

        $datesAndAmounts = $this->createDatesAndAmounts($amount, $frequency, $months);
        return json_encode($datesAndAmounts);

    }
}

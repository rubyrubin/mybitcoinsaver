<?php

namespace App\Http\Controllers;

use App\MarketPrice;
use GuzzleHttp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarketPriceController extends Controller
{
    /**
     * We want an epoch between the beginning of bitcoin and today. If the epoch requested
     * isn't exact (i.e. in between 12AM UTC time), then we find the previous day's value
     *
     * @param int $epoch
     * @return mixed The nearest epoch at 12AM UTC, or error string if not valid
     */
    private function validateEpoch($epoch){
        $firstDayOfBitcoin = 1230940800;
        $millisecondsTwoDays = 172800;  // the api returns values in 2 day increments
        $epoch = intval($epoch);
        if(!$epoch) {
            return "Epoch was not an int, could not be converted to an int, or was 0";
        }
        // This is the UTC of the very first day of bitcoin existence
        if($epoch < $firstDayOfBitcoin) {
            return "Epoch is before the start of bitcoin";
        }
        if($epoch > time() - $millisecondsTwoDays) {
            return "Epoch is in the future or too recent for API";
        }
        // This is turning the epoch into the last known data point (up to two days behind)
        if( ($epoch - $firstDayOfBitcoin) % $millisecondsTwoDays > 0) {
            Log::debug("About to round the epoch to the previous known value");
            return $epoch - (($epoch - $firstDayOfBitcoin) % $millisecondsTwoDays);
        }
        return $epoch;
    }

    /**
     * Helper function to load the data from blockchain.info api into a local database (better performance, no worry
     * about api throttling, etc.)
     *
     * @return bool Success flag
     */
    private function loadDataFromBlockchainAPI() {
        Log::info("Calling blockchain.info to get all data");
        $client = new GuzzleHttp\Client();
        $res = $client->get('https://blockchain.info/charts/market-price?timespan=1800months&format=json');
        if($res->getStatusCode() == 200) {
            $jsonResponse = json_decode($res->getBody());
            $values = $jsonResponse->values;
            foreach($values as $value) {
                $marketPriceRow = new MarketPrice();
                $marketPriceRow->epoch = $value->x;
                $marketPriceRow->value = $value->y;
                $marketPriceRow->save();
            }
        }
        else {
            return false;
        }
        return true;
    }

    /**
     * Not currently used, but get a single datapoint from the database. Proved to be too inefficient
     *
     * @param $epoch
     * @return \Illuminate\Http\JsonResponse
     */
    public function retrieveOne($epoch)
    {
        $epoch = $this->validateEpoch($epoch);
        if (is_string($epoch)) {
            Log::error($epoch);
            return response()->json(['error' => $epoch], 422);
        }
        Log::debug("Trying to find epoch " . $epoch);
        $marketPriceObject = MarketPrice::find($epoch);
        if($marketPriceObject == null) {
            // for simplicity sake and because the data size is currently managable in just one call
            // we will get all data lazily if not already loaded into the database. By putting in 1800 months
            // we will have data going longer than the life of this code
            // This should generally only happen when there is newer data available
            // TODO optimise to get smaller chunk of data on a regular basis instead
            if($this->loadDataFromBlockchainAPI()){
                $marketPriceObject = MarketPrice::find($epoch);
            }
            else {
                return response()->json(['error' => 'Could not load data from blockchain.info'], 400);
            }
        }
        return $marketPriceObject->value;
    }

    /**
     * Main entry for getting the data from the database, which lazily loads from the blockchain api.
     *
     * @param $fromEpoch
     * @param $toEpoch
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function retrieveMany($fromEpoch, $toEpoch) {
        $fromEpoch = $this->validateEpoch($fromEpoch);
        if (is_string($fromEpoch)) {
            $error = 'from: ' . $fromEpoch;
            Log::error($error);
            return response()->json(['error' => $error], 422);
        }
        $toEpoch = $this->validateEpoch($toEpoch);
        if (is_string($toEpoch)) {
            $error = 'to: ' . $toEpoch;
            Log::error($error);
            return response()->json(['error' => $error], 422);
        }
        if($fromEpoch > $toEpoch) {
            return response()->json(['error' => "fromEpoch cannot be greater than toEpoch"], 422);
        }
        // since the maximum total size of the data is less than 3000 records, any valid epoch should be fine
        $allBitcoinValues = MarketPrice::where('epoch', '>=', $fromEpoch)->where('epoch', '<=', $toEpoch)->get();
        if(!$allBitcoinValues || !$allBitcoinValues->count()) {
            // for simplicity sake and because the data size is currently managable in just one call
            // we will get all data lazily if not already loaded into the database. By putting in 1800 months
            // we will have data going longer than the life of this code
            // This should generally only happen when there is newer data available
            // TODO optimise to get smaller chunk of data on a regular basis instead
            if($this->loadDataFromBlockchainAPI()){
                $allBitcoinValues = MarketPrice::where('epoch', '>=', $fromEpoch)->where('epoch', '<=', $toEpoch)->get();
            }
            else {
                return response()->json(['error' => 'Could not load data from blockchain.info'], 400);
            }
        }
        return json_encode($allBitcoinValues);
    }
}

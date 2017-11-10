<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Charts;
use GuzzleHttp;
use Input;
use Illuminate\Support\Facades\Log;

class ChartController extends Controller
{
    private static $hostname;

    /**
     * Pulls the savings summary info from the local API call
     *
     * @param $amount
     * @param $months
     * @return null|string
     */
    private function getDataFromAPI($amount, $months) {
        $client = new GuzzleHttp\Client();
        $res = $client->get(self::$hostname . '/api/savings_summary/amount='.$amount.'&frequency=weekly&months='.$months.'&currency=NZD');
        $allValuesJson = null;
        if($res->getStatusCode() == 200) {
            $allValuesJson = (string)$res->getBody();
        } else {
            abort(400, "Could not call internal route to get savings calculations");
        }
        return $allValuesJson;
    }

    /**
     * The entry point for the chart (GET)
     *
     * @param Request $request
     * @return $this
     */
    public function chart(Request $request)
    {
        self::$hostname = $request->getHttpHost();
        $data = $this->getDataFromAPI(10, 1);
        return view('savingsChart')->with('data', $data);

    }

    /**
     * Used to update the chart (POST)
     *
     * @param Request $request
     * @return $this
     */
    public function updateChart(Request $request)
    {
        self::$hostname = $request->getHttpHost();
        $amount = Input::get('amount');
        $months = Input::get('months') + 1; // zero based array
        Log::debug("months: ".$months);
        $data = $this->getDataFromAPI($amount, $months);
        return view('savingsChart')->with('data', $data);

    }
}

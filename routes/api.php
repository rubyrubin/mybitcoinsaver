<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('market_prices', 'MarketPriceController@index');
Route::get('market_prices/{epoch}', 'MarketPriceController@retrieveOne');
Route::get('market_prices/many/from={fromEpoch}&to={toEpoch}', 'MarketPriceController@retrieveMany');

Route::get('savings_summary/amount={amount}&frequency={frequency}&months={months}&currency={currency}', 'SavingsSummaryController@calculate');


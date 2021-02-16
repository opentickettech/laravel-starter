<?php

use App\Models\CompanyAccessToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Misc\Openticket\OTApi;

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

Route::post('/webhook/{company_id}', function (Request $request, $company_id) {
    $companyAccessToken = CompanyAccessToken::find($company_id);
    if(is_null($companyAccessToken)){
        abort(404, 'Company not found');
    }

    $orderId = $guid = $request->get('guid');
    $client = new OTApi($companyAccessToken);
    $order = $client->get("/order/$orderId");

    Log::info("New order: $order->email ($order->guid)");

    // Do stuff with order or something else

    // Signal success, any content will do, but retrying is stopped after status code 200
    return response('' , 200);
});

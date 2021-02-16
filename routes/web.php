<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    if(Cookie::has('user_id')){
        return redirect('/dashboard');
    }
    return view('welcome');
});

Route::get('/logout', function () {
    \Cookie::queue(Cookie::forget('user_id'));
    return redirect('/');
});

Route::get('/connect', function () {
    return Socialite::driver('opentickettech')->redirect();
});

Route::get('/redirect', function (Request $request) {
    if ($request->has('code')) {
        $response = Socialite::driver("opentickettech")->getAccessTokenResponse($request->get("code"));

        // Find or create user
        $user = \App\Models\User::firstOrCreate([
            'guid' => $response['info']['user']['guid'],
        ], [
            'name'  => $response['info']['user']['name'],
            'email' => $response['info']['user']['email'],
        ]);
        $cid = $response['info']['company']['guid'];
        if(is_null($companyAccessToken = \App\Models\CompanyAccessToken::find($cid))){
            $companyAccessToken = \App\Models\CompanyAccessToken::create([
                'guid' => $cid,
                'name' => $response['info']['company']['name'],
                'access_token' => $response['access_token'],
                'expires_at' => \Carbon\Carbon::now()->timestamp + $response['expires_in'],
                'refresh_token' => $response['refresh_token'],
                'refresh_token_expires_at' =>\Carbon\Carbon::now()->timestamp + $response['refresh_token_expires_in'],
            ]);
        }
        else{
            Log::info("cid: $cid already connected");
        }

        $user->allCompanyAccessTokens()->syncWithoutDetaching($companyAccessToken);

        $user->last_company_id = $companyAccessToken->guid;
        $user->save();

        Cookie::queue('user_id', $user->guid, 60*24*60); // remember 60 days;
        return redirect('/dashboard');
    }

    return redirect('/');
});

Route::get('/dashboard', function () {
    $userId = Cookie::get('user_id');
    if (is_null($userId)) {
        return redirect('/');
    }
    $userObj = \App\Models\User::find($userId);
    $user = (object) Socialite::driver('opentickettech')->userFromToken($userObj->companyAccessToken->access_token);

    $companies = $userObj->allCompanyAccessTokens;

    return view('dashboard', ['user' => $user, 'companies' => $companies]);
});

Route::get('/company/{company_id}', function ($company_id) {
    $userId = Cookie::get('user_id');
    if (is_null($userId)) {
        return redirect('/');
    }
    $userObj = \App\Models\User::find($userId);
    $user = (object) Socialite::driver('opentickettech')->userFromToken($userObj->companyAccessToken->access_token);

    if(is_null($companyAccessToken = $userObj->allCompanyAccessTokens()->find($company_id))){
        abort(404, 'Company not found');
    }

    $client = new \Misc\Openticket\OTApi($companyAccessToken);
    $upcomingEvents = $client->get('/event/upcoming');

    return view('company', ['user' => $user, 'company' => $companyAccessToken, 'upcoming_events' => $upcomingEvents]);
});

/**
 * Can be used for public / visitors
 */
Route::get('/public/{company_id}', function ($company_id) {
    $companyAccessToken = \App\Models\CompanyAccessToken::find($company_id);
    if(is_null($companyAccessToken)){
        abort(404, 'Company not found');
    }

    return view('public', ['company' => $companyAccessToken]);
});

/**
 * Eg used for public / visitors forms where you dont want to show company_id to users
 */
Route::get('/public_encrypted/{encrypted_company_id}', function ($encrypted_company_id) {
    // Sample, but you can use any other method
    // $string = 'String to decrypt';
    // $encrypted = Crypt::encryptString($string);
    // $decrypted = Crypt::decryptString($encrypted);

    $companyAccessToken = \App\Models\CompanyAccessToken::find(Crypt::decryptString($encrypted_company_id));
    if(is_null($companyAccessToken)){
        abort(404, 'Company not found');
    }

    return view('public', ['company' => $companyAccessToken]);
});

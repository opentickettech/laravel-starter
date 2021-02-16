<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Misc\Openticket\OTApi;
use Socialite;

class CompanyAccessToken extends Model {

    use SoftDeletes;

    protected $primaryKey = 'guid';
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'guid',
        'name',
        'access_token',
        'expires_at',
        'refresh_token',
        'refresh_token_expires_at',
    ];

    public function users() {
        return $this->belongsToMany(User::class);
    }

    public function needsRefresh() {
        // check if access token expires in t minus 1 hour
        // check if refresh token expires in t minus 5 days
        return (time() > ($this->expires_at - (60 * 60 * 1))) || (time() > ($this->refresh_token_expires_at - (24 * 60 * 60 * 5))) ;
    }

    public function refreshToken() {
        $response = Socialite::driver('opentickettech')->getAccessTokenRefreshResponse($this->refresh_token);

        if (!isset($response['access_token'])) {
            Log::warning('updating access token for ' . $this->guid . ' failed');
            throw new \Exception('updating access token for ' . $this->guid . ' failed');
        } else {
            $this->access_token = $response['access_token'];
            $this->expires_at = \Carbon\Carbon::now()->timestamp + $response['expires_in'];
            $this->refresh_token = $response['refresh_token'];
            $this->refresh_token_expires_at = \Carbon\Carbon::now()->timestamp + $response['refresh_token_expires_in'];
            $this->save();
        }
    }
}

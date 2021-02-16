<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class User extends Model {

    use Notifiable;

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
        'email',
        'last_access_token_id',
    ];

    public function companyAccessToken() {
        return $this->hasOne(CompanyAccessToken::class, 'guid', 'last_company_id');
    }

    public function allCompanyAccessTokens() {
        return $this->belongsToMany(CompanyAccessToken::class, 'company_access_token_user', 'user_id', 'company_access_token_id');
    }
}

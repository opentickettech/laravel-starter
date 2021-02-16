<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTokensTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        // Drop default laravel users table
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_resets');

        Schema::create('users', function (Blueprint $table) {
            $table->string('guid')->primary();
            $table->string('name');
            $table->string('email');
            $table->string('last_company_id')->nullable();
            $table->timestamps();
        });

        Schema::create('company_access_tokens', function (Blueprint $table) {
            $table->string('guid', 36)->primary()->comment('company_id');
            $table->string('name')->comment('company_name');
            $table->string('access_token', 2047)->nullable();
            $table->unsignedBigInteger('expires_at')->nullable();
            $table->string('refresh_token', 1023)->nullable();
            $table->unsignedBigInteger('refresh_token_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('company_access_token_user', function (Blueprint $table) {
            $table->string('user_id', 36);
            $table->string('company_access_token_id', 36);
            $table->foreign('user_id')->references('guid')->on('users');
            $table->foreign('company_access_token_id')->references('guid')->on('company_access_tokens');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('last_company_id')->references('guid')->on('company_access_tokens');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::dropIfExists('access_tokens');
    }
}

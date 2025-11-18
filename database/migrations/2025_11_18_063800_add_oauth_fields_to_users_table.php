<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('oauth_provider')->nullable()->after('password')->comment('OAuth provider: google, facebook, alipay, wechat, wallet');
            $table->string('oauth_provider_id')->nullable()->after('oauth_provider')->comment('OAuth provider user ID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['oauth_provider', 'oauth_provider_id']);
        });
    }
};

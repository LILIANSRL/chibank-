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
        Schema::create('wallet_authentications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type', 50)->default('user')->comment('user, merchant, agent, admin');
            $table->text('wallet_address');
            $table->string('blockchain', 100);
            $table->string('wallet_provider', 100)->nullable()->comment('metamask, walletconnect, coinbase, trust, etc');
            $table->text('public_key')->nullable();
            $table->text('signature')->nullable();
            $table->string('nonce', 255)->nullable()->comment('Random nonce for signature verification');
            $table->timestamp('nonce_expires_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('login_ip', 45)->nullable();
            $table->text('login_user_agent')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'user_type']);
            $table->index('wallet_address');
            $table->index('blockchain');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallet_authentications');
    }
};

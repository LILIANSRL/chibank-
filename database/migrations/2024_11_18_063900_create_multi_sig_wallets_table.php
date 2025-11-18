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
        Schema::create('multi_sig_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('wallet_type', 50)->default('multi_sig')->comment('multi_sig, single');
            $table->unsignedBigInteger('owner_id');
            $table->string('owner_type', 50)->comment('user, merchant, agent, admin');
            $table->string('blockchain', 100);
            $table->string('currency_code', 10);
            $table->text('address')->nullable();
            $table->text('public_key')->nullable();
            $table->text('contract_address')->nullable()->comment('For smart contract wallets');
            $table->integer('required_signatures')->default(1)->comment('Number of signatures required');
            $table->integer('total_signers')->default(1);
            $table->decimal('balance', 28, 8)->default(0);
            $table->text('wallet_data')->nullable()->comment('JSON data for additional wallet info');
            $table->boolean('status')->default(true);
            $table->timestamps();
            
            $table->index(['owner_id', 'owner_type']);
            $table->index('blockchain');
            $table->index('currency_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('multi_sig_wallets');
    }
};

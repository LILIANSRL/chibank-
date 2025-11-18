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
        Schema::create('wallet_signers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('multi_sig_wallet_id');
            $table->unsignedBigInteger('signer_id');
            $table->string('signer_type', 50)->comment('user, merchant, agent, admin');
            $table->string('signer_name', 255);
            $table->string('signer_email', 255);
            $table->text('public_key')->nullable();
            $table->text('wallet_address')->nullable();
            $table->integer('weight')->default(1)->comment('Signature weight for weighted multi-sig');
            $table->boolean('is_owner')->default(false);
            $table->boolean('can_initiate')->default(true);
            $table->boolean('can_approve')->default(true);
            $table->boolean('status')->default(true);
            $table->timestamps();
            
            $table->foreign('multi_sig_wallet_id')->references('id')->on('multi_sig_wallets')->onDelete('cascade');
            $table->unique(['multi_sig_wallet_id', 'signer_id', 'signer_type'], 'unique_wallet_signer');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallet_signers');
    }
};

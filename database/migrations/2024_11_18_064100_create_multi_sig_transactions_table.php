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
        Schema::create('multi_sig_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('multi_sig_wallet_id');
            $table->string('transaction_type', 50)->comment('send, receive, internal_transfer, contract_call');
            $table->string('trx_id', 100)->unique();
            $table->text('from_address');
            $table->text('to_address');
            $table->decimal('amount', 28, 8);
            $table->string('currency', 10);
            $table->decimal('fee', 28, 8)->default(0);
            $table->text('transaction_data')->nullable()->comment('JSON data for transaction details');
            $table->text('blockchain_txn_hash')->nullable();
            $table->integer('required_approvals');
            $table->integer('current_approvals')->default(0);
            $table->string('status', 50)->default('pending')->comment('pending, approved, rejected, executed, failed');
            $table->unsignedBigInteger('initiated_by');
            $table->string('initiator_type', 50);
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            
            $table->foreign('multi_sig_wallet_id')->references('id')->on('multi_sig_wallets')->onDelete('cascade');
            $table->index('status');
            $table->index('transaction_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('multi_sig_transactions');
    }
};

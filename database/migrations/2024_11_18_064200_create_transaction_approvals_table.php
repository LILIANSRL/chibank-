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
        Schema::create('transaction_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('multi_sig_transaction_id');
            $table->unsignedBigInteger('signer_id');
            $table->string('signer_type', 50);
            $table->string('action', 20)->comment('approve, reject');
            $table->text('signature')->nullable();
            $table->text('comment')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->foreign('multi_sig_transaction_id', 'fk_approval_transaction')->references('id')->on('multi_sig_transactions')->onDelete('cascade');
            $table->unique(['multi_sig_transaction_id', 'signer_id', 'signer_type'], 'unique_transaction_approval');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_approvals');
    }
};

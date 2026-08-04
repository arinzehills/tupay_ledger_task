<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('destination_wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->string('status'); // pending, completed, failed, reversed
            $table->bigInteger('source_amount'); // Amount deducted from source
            $table->bigInteger('destination_amount'); // Amount added to destination
            $table->string('reference_id')->nullable()->unique(); // For idempotency
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}

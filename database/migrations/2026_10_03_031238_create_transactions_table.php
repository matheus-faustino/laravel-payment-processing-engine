<?php

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('source_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignUuid('destination_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->bigInteger('amount');
            $table->enum('type', TransactionType::cases());
            $table->enum('status', TransactionStatus::cases())->default(TransactionStatus::PENDING);
            $table->uuid('idempotency_key')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

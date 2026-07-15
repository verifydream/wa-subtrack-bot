<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('service_name');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('IDR');
            $table->decimal('amount_idr', 12, 2)->nullable();
            $table->integer('billing_day');
            $table->string('billing_cycle')->default('monthly');
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('next_billing_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

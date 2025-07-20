<?php

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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('category_id')->references('id')->on('categories')->cascadeOnDelete();
            $table->foreignId('freelancer_id')->references('id')->on('freelancers')->cascadeOnDelete();
            $table->decimal('quoted_price', 10, 2);
            $table->enum('billing_unit', ['per_hour', 'per_day', 'per_week', 'per_month', 'fixed_price']);
            $table->string('city');
            $table->string('country');
            $table->enum('status', ['in_review', 'accepted', 'canceled', 'complete'])->default('in_review');
            $table->enum('payment_method', ['cash', 'online'])->default('cash');
            $table->date('start_date');
            $table->text('description');
            $table->tinyInteger('freelancer_rating')->nullable();
            $table->tinyInteger('client_rating')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

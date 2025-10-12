<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('fee_allocation_id');
            $table->decimal('amount', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('fine', 10, 2)->default(0);
            $table->string('payment_method');
            $table->date('payment_date');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('collected_by');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('fee_allocation_id')->references('id')->on('fee_allocations');
            $table->foreign('collected_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('fee_payments');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fee_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('class');
            $table->unsignedBigInteger('fee_type_id');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->timestamps();

            $table->foreign('fee_type_id')->references('id')->on('fee_types');
        });
    }

    public function down()
    {
        Schema::dropIfExists('fee_groups');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->enum('name', ['Term 1', 'Term 2', 'Term 3']);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('year');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('terms');
    }
};
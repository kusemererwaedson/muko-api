<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Remove class_id and capacity from streams table
        Schema::table('streams', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropColumn(['class_id', 'capacity']);
        });

        // Create pivot table for class-stream many-to-many relationship
        Schema::create('class_stream', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('stream_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['class_id', 'stream_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('class_stream');
        
        Schema::table('streams', function (Blueprint $table) {
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->integer('capacity')->nullable();
        });
    }
};
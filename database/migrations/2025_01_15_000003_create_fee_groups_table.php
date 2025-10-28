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
            $table->foreignId('fee_type_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fee_groups');
    }
};
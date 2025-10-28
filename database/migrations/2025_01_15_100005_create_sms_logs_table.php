<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->integer('recipient_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->enum('status', ['sent', 'scheduled', 'failed'])->default('sent');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sms_logs');
    }
};
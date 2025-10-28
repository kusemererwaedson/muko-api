<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['level', 'amount_paid', 'amount_due', 'amount_overdue']);
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('level')->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0.00);
            $table->decimal('amount_due', 10, 2)->default(0.00);
            $table->decimal('amount_overdue', 10, 2)->default(0.00);
        });
    }
};

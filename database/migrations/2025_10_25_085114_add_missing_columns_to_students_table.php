<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            // Add missing columns
            if (!Schema::hasColumn('students', 'register_no')) {
                $table->string('register_no')->unique()->after('id');
            }
            if (!Schema::hasColumn('students', 'level')) {
                $table->string('level')->nullable()->after('stream_id');
            }
            if (!Schema::hasColumn('students', 'fee_types')) {
                $table->text('fee_types')->nullable()->after('level');
            }
            if (!Schema::hasColumn('students', 'fee_groups')) {
                $table->text('fee_groups')->nullable()->after('fee_types');
            }
            if (!Schema::hasColumn('students', 'amount_paid')) {
                $table->decimal('amount_paid', 10, 2)->default(0)->after('fee_groups');
            }
            if (!Schema::hasColumn('students', 'amount_due')) {
                $table->decimal('amount_due', 10, 2)->default(0)->after('amount_paid');
            }
            if (!Schema::hasColumn('students', 'amount_overdue')) {
                $table->decimal('amount_overdue', 10, 2)->default(0)->after('amount_due');
            }
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'register_no', 'level', 'fee_types', 'fee_groups', 'amount_paid', 'amount_due', 'amount_overdue'
            ]);
        });
    }
};

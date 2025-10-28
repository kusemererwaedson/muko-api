<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            // Rename register_no to registration_no if it exists
            if (Schema::hasColumn('students', 'register_no') && !Schema::hasColumn('students', 'registration_no')) {
                $table->renameColumn('register_no', 'registration_no');
            }
            
            // Drop fee_types and fee_groups columns as we use pivot tables
            if (Schema::hasColumn('students', 'fee_types')) {
                $table->dropColumn('fee_types');
            }
            if (Schema::hasColumn('students', 'fee_groups')) {
                $table->dropColumn('fee_groups');
            }
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'registration_no')) {
                $table->renameColumn('registration_no', 'register_no');
            }
            
            $table->text('fee_types')->nullable();
            $table->text('fee_groups')->nullable();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['register_no', 'section', 'roll', 'email', 'phone']);
            
            // Rename class to class_id and make it foreign key
            $table->dropColumn('class');
            
            // Add new columns
            $table->string('lin')->unique()->after('id');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->foreignId('class_id')->nullable()->constrained()->after('last_name');
            $table->foreignId('stream_id')->nullable()->constrained()->after('class_id');
            $table->string('picture')->nullable()->after('address');
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            // Restore old columns
            $table->string('register_no')->unique();
            $table->string('class');
            $table->string('section')->nullable();
            $table->string('roll')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            
            // Drop new columns
            $table->dropForeign(['class_id']);
            $table->dropForeign(['stream_id']);
            $table->dropColumn(['lin', 'middle_name', 'class_id', 'stream_id', 'picture']);
        });
    }
};
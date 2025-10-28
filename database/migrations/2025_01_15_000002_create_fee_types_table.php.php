<?php // database/migrations/2025_10_25_000003_add_extra_fields_to_fee_types_table.php ?>
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::create('fee_types', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->text('description')->nullable();
        $table->date('from')->nullable();         // extra field
        $table->date('to')->nullable();           // extra field
        $table->unsignedBigInteger('created_by')->nullable();  // extra field
        $table->unsignedBigInteger('edited_by')->nullable();   // extra field
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('fee_types');
}
};

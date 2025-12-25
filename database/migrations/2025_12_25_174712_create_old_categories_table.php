<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('old_categories', function (Blueprint $table) {
            $table->bigIncrements('old_category_id');
            $table->string('cat_name');
            $table->integer('parent_cat_id');
            $table->text('info')->nullable();
            $table->string('meta_desc');
            $table->text('cat_path');
            $table->text('child');
            $table->integer('numlink')->default(0);
            $table->string('img')->default('default.png');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('old_categories');
    }
};

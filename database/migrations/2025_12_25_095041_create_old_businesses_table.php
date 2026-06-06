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
        Schema::create('old_businesses', function (Blueprint $table) {
            $table->bigIncrements('old_business_id');
            $table->string('ip');
            $table->string('timehour');
            $table->string('name');
            $table->string('mail');
            $table->string('caption');
            $table->string('url');
            $table->text('site_desc');
            $table->integer('hits')->default(0);
            $table->integer('category_id')->default(0);
            $table->string('approved')->default('no');
            $table->integer('featured')->default(0);
            $table->string('pr')->nullable();
            $table->string('reciurl')->nullable();
            $table->string('stime')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('old_businesses');
    }
};

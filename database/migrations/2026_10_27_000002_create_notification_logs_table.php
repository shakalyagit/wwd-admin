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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->bigIncrements('notification_log_id');
            $table->string('type', 50);
            $table->unsignedBigInteger('business_id');
            $table->string('recipient_email');
            $table->string('subject');
            // Days after submission for automatic sends (0, 1, 3, 15, 30, ...); null for manual sends
            $table->unsignedSmallInteger('step')->nullable();
            $table->string('trigger', 10)->default('auto');
            $table->string('status', 10);
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['type', 'business_id', 'step']);
            $table->index('business_id');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};

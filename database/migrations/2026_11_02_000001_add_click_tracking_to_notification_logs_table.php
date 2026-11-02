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
        // The email's action button points to /t/{tracking_token}, which records the click and forwards to target_url
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->string('tracking_token', 40)->nullable()->unique()->after('error');
            $table->string('target_url', 500)->nullable()->after('tracking_token');
            $table->timestamp('clicked_at')->nullable()->after('sent_at');
            $table->unsignedInteger('click_count')->default(0)->after('clicked_at');
            $table->index(['type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropIndex(['type', 'created_at']);
            $table->dropUnique(['tracking_token']);
            $table->dropColumn(['tracking_token', 'target_url', 'clicked_at', 'click_count']);
        });
    }
};

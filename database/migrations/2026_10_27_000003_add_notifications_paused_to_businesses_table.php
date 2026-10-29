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
        // businesses is shared with the public WWD site; only add the column if it is missing
        if (!Schema::hasColumn('businesses', 'notifications_paused')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->boolean('notifications_paused')->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('businesses', 'notifications_paused')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->dropColumn('notifications_paused');
            });
        }
    }
};

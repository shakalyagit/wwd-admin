<?php

use Database\Seeders\EmailTemplateSeeder;
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
        // Admin-owned: businesses an admin listed from another directory (outreach). Not shown on the public site.
        // The business itself lives in the shared businesses table (is_admin_posted = 1).
        Schema::create('admin_listings', function (Blueprint $table) {
            $table->bigIncrements('admin_listing_id');
            $table->unsignedBigInteger('business_id')->unique();
            // Where the admin found the business, for the claim-rate-by-source report (admin reference only)
            $table->string('source_name', 100)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->unsignedBigInteger('added_by')->nullable();
            // Filled once the owner has claimed the business (businesses.is_claimed = 1)
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->index('source_name');
            $table->index('created_at');
        });

        // Adds the listing_invite email template (insert-only, never overwrites admin edits)
        (new EmailTemplateSeeder())->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_listings');
    }
};

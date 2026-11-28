<?php

use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Admin-owned: one row per uploaded Excel file
        Schema::create('admin_listing_imports', function (Blueprint $table) {
            $table->bigIncrements('admin_listing_import_id');
            $table->string('file_name');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('uploaded_rows')->default(0);
            $table->unsignedInteger('needs_fix_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            // [{"row": 5, "name": "Acme", "reason": "Website already listed as …"}] for rows not imported
            $table->json('skipped_details')->nullable();
            $table->timestamps();
        });

        // Admin-owned: one row per business from an Excel file, reviewed and approved before it becomes a live business
        Schema::create('admin_listing_drafts', function (Blueprint $table) {
            $table->bigIncrements('admin_listing_draft_id');
            $table->unsignedBigInteger('admin_listing_import_id');
            $table->unsignedInteger('row_number');
            // uploaded, needs_fix, approved, skipped
            $table->string('status', 20)->default('uploaded');
            // ["Unknown category 'Plumbers'", …] while status = needs_fix
            $table->json('problems')->nullable();
            $table->string('business_name')->nullable();
            $table->string('business_website')->nullable();
            $table->string('business_email')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            // The category as typed in the Excel (kept when it doesn't match a WWD category)
            $table->string('category_text')->nullable();
            $table->string('country_code', 8)->nullable();
            $table->string('business_phone', 45)->nullable();
            $table->text('business_desc')->nullable();
            $table->string('street_line_1', 50)->nullable();
            $table->string('street_line_2', 50)->nullable();
            $table->string('city', 50)->nullable();
            $table->string('province_state_territory', 50)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->unsignedBigInteger('ref_country_id')->nullable();
            $table->string('country_text', 100)->nullable();
            $table->string('source_name', 100)->nullable();
            $table->string('source_url', 500)->nullable();
            // Logo added on the review page, kept privately in storage/app/listing-drafts until approval
            $table->string('logo_file')->nullable();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('skip_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'admin_listing_import_id']);
        });

        // The claim invite gets a "View your listing" link (kept if an admin already added it)
        DB::table('email_templates')
            ->where('template_key', 'listing_invite')
            ->where('body', 'not like', '%{{ListingURL}}%')
            ->update(['body' => DB::raw('CONCAT(body, ' . DB::getPdo()->quote(EmailTemplateSeeder::LISTING_LINK) . ')'), 'updated_at' => now()]);
        DB::table('email_templates')
            ->where('template_key', 'listing_invite')
            ->where('variables', 'not like', '%ListingURL%')
            ->update(['variables' => DB::raw('JSON_ARRAY_APPEND(variables, \'$\', CAST(' . DB::getPdo()->quote(json_encode(EmailTemplateSeeder::LISTING_URL_VARIABLE, JSON_UNESCAPED_SLASHES)) . ' AS JSON))')]);

        // New installs: inserts listing_invite with the link (insert-only)
        (new EmailTemplateSeeder())->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_listing_drafts');
        Schema::dropIfExists('admin_listing_imports');
    }
};

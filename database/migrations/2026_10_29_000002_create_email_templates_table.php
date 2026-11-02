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
        // Email template master shared by WWD Admin, WWD Business and WWD Listing (same database).
        // Each project loads a template by template_key, fills {{Placeholders}} and sends it itself.
        Schema::create('email_templates', function (Blueprint $table) {
            $table->bigIncrements('email_template_id');
            $table->string('template_key', 80)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            // Which project sends it: admin, business, listing, all
            $table->string('project', 20)->default('all');
            // event (after an action), scheduled (cron), manual (sent by an admin)
            $table->string('trigger_type', 20)->default('event');
            // System templates are referenced by code: content is editable, key can't change, can't be deleted
            $table->boolean('is_system')->default(0);
            $table->unsignedBigInteger('email_layout_id')->nullable();
            $table->string('subject');
            $table->string('preheader')->nullable();
            $table->string('header_title')->nullable();
            $table->string('header_subtitle')->nullable();
            $table->longText('body');
            $table->string('button_text', 100)->nullable();
            $table->string('button_url')->nullable();
            $table->string('footer_note')->nullable();
            // [{"name": "BusinessName", "sample": "Sunrise Bakery", "description": "..."}]
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(1);
            // Scheduled templates: only businesses submitted on/after this date are included
            $table->timestamp('starts_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['project', 'is_active']);
        });

        (new EmailTemplateSeeder())->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};

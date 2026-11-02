<?php

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
        // Master email layout shared by WWD Admin, WWD Business and WWD Listing (same database)
        Schema::create('email_layouts', function (Blueprint $table) {
            $table->bigIncrements('email_layout_id');
            $table->string('name');
            // Full HTML; template parts go into {{Content}}, {{HeaderTitle}}, {{HeaderSubtitle}}, {{Preheader}}, {{FooterNote}}
            $table->longText('html');
            // Button markup used when a template has button text + link: {{ButtonText}}, {{ButtonURL}}
            $table->text('button_html');
            $table->string('logo_url')->nullable();
            $table->string('brand_color', 20)->default('#dd3f3b');
            $table->string('site_url')->nullable();
            $table->string('support_email')->nullable();
            $table->string('footer_text')->nullable();
            $table->boolean('is_default')->default(0);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        DB::table('email_layouts')->insert([
            'name'          => 'WWD default',
            'html'          => file_get_contents(database_path('seeders/email/layout.html')),
            'button_html'   => '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px auto;"><tr><td style="border-radius:6px; background:{{BrandColor}};"><a href="{{ButtonURL}}" style="display:inline-block; padding:14px 28px; color:#ffffff; font-size:16px; font-weight:bold; text-decoration:none; border-radius:6px;">{{ButtonText}}</a></td></tr></table>',
            'logo_url'      => 'http://webadmin.worldweb-directory.com/assets/img/logos/logo.png',
            'brand_color'   => '#dd3f3b',
            'site_url'      => 'https://www.worldweb-directory.com',
            'support_email' => 'info@worldweb-directory.com',
            'footer_text'   => 'You’re receiving this email because you have an account or business profile on WWD.',
            'is_default'    => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_layouts');
    }
};

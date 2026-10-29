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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->bigIncrements('notification_template_id');
            $table->string('type', 50)->unique();
            $table->string('name');
            $table->string('subject');
            $table->longText('body');
            $table->boolean('is_active')->default(1);
            // Only businesses submitted on/after this date get automatic reminders
            $table->timestamp('starts_at')->nullable();
            $table->timestamps();
        });

        DB::table('notification_templates')->insert([
            'type'       => 'verification_reminder',
            'name'       => 'Business verification reminder',
            'subject'    => 'Verify your business to get listed on WWD',
            'body'       => '<p><strong>Dear {owner_name},</strong></p>'
                . '<p>Thank you for submitting <strong>{business_name}</strong> to World Web Directory.</p>'
                . '<p>Your business profile is <strong>not verified yet</strong>. Verification is necessary for listing your business profile on WWD. Until it is verified, your business will not be visible to customers.</p>'
                . '<p>It only takes a minute:</p>'
                . '<p style="text-align:center;"><a href="{claim_url}" style="background-color:#e53935; color:#ffffff; text-decoration:none; padding:14px 28px; border-radius:4px; font-size:16px; display:inline-block;">Verify my business</a></p>'
                . '<p>If the button does not work, copy this link into your browser: {claim_url}</p>',
            'is_active'  => 1,
            'starts_at'  => now()->startOfDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};

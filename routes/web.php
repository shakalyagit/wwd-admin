<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminListingController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RiskRegisterController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use App\Livewire\AddBusiness;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth'], function () {
    Route::get('dashboard', [AdminAuthController::class, 'dashboard'])->name('dashboard');
    Route::get('logout', [AdminAuthController::class, 'logout'])->name('logout');
    Route::post('change-password', [AdminAuthController::class, 'change_password'])->name('change_password');
    Route::post('update-profile', [AdminAuthController::class, 'update_profile'])->name('update_profile');

    //Business Listing Route
    Route::get('business-list', [BusinessController::class, 'business_list'])->name('business_list');
    Route::get('business-approval-queue', [BusinessController::class, 'business_approval_queue'])->name('business_approval_queue');
    // Route::get('add-business', [BusinessController::class, 'add_business'])->name('add_business');
    Route::get('edit-business/{business_id}', [BusinessController::class, 'edit_business'])->name('edit_business');
    Route::post('edit-business-action/{business_id}', [BusinessController::class, 'edit_business_action'])->name('edit_business_action');
    Route::post('edit-business-address-action/{business_id}', [BusinessController::class, 'edit_business_address_action'])->name('edit_business_address_action');
    Route::post('edit-business-hours-action/{business_id}', [BusinessController::class, 'edit_business_hours_action'])->name('edit_business_hours_action');
    Route::post('edit-user-action/{business_id}', [BusinessController::class, 'edit_user_action'])->name('edit_user_action');
    Route::post('edit-business-logo-action/{business_id}', [BusinessController::class, 'edit_business_logo_action'])->name('edit_business_logo_action');
    Route::post('/business/{business}/approve', [BusinessController::class, 'business_approve'])->name('business_approve');
    Route::post('/business/{business}/reject', [BusinessController::class, 'business_reject'])->name('business_reject');
    Route::get('/business-list-filter', [BusinessController::class, 'business_list_filter'])->name('business_list_filter');
    Route::post('/check-new-business', [BusinessController::class, 'check_new_business'])->name('check_new_business');

    // Admin listings: businesses found on other directories, listed by an admin and invited to claim
    Route::get('/business/create', [AdminListingController::class, 'business_create'])->name('business_create');
    Route::post('/business/store', [AdminListingController::class, 'business_store'])->name('business_store');
    Route::post('/business/check', [AdminListingController::class, 'business_check'])->name('business_check');
    Route::get('/admin-listings', [AdminListingController::class, 'admin_listings'])->name('admin_listings');
    Route::post('/admin-listing-resend/{business_id}', [AdminListingController::class, 'admin_listing_resend'])->name('admin_listing_resend');
    // Excel import: sample, upload, drafts to review, approve
    Route::get('/business-import', [AdminListingController::class, 'listing_import'])->name('listing_import');
    Route::get('/business-import/sample', [AdminListingController::class, 'listing_import_sample'])->name('listing_import_sample');
    Route::post('/business-import/upload', [AdminListingController::class, 'listing_import_upload'])->name('listing_import_upload');
    Route::post('/business-import/approve', [AdminListingController::class, 'listing_drafts_approve'])->name('listing_drafts_approve');
    Route::get('/business-import/draft/{draft_id}', [AdminListingController::class, 'listing_draft'])->name('listing_draft');
    Route::get('/business-import/draft/{draft_id}/logo', [AdminListingController::class, 'listing_draft_logo'])->name('listing_draft_logo');
    Route::post('/business-import/draft/{draft_id}', [AdminListingController::class, 'listing_draft_save'])->name('listing_draft_save');
    Route::post('/business-import/draft/{draft_id}/skip', [AdminListingController::class, 'listing_draft_skip'])->name('listing_draft_skip');

    Route::get('/users', [UserController::class, 'users'])->name('users');
    Route::post('/users-status-update', [UserController::class, 'users_status_update'])->name('users_status_update');
    Route::get('/reviews', [ReviewController::class, 'reviews'])->name('reviews');

    //Old Business Listing Route
    Route::get('old-business-list', [BusinessController::class, 'old_business_list'])->name('old_business_list');
    Route::get('edit-old-business/{old_business_id}', [BusinessController::class, 'edit_old_business'])->name('edit_old_business');
    Route::post('/old-business-action/{old_business_id}', [BusinessController::class, 'old_business_action'])->name('old_business_action');
    Route::post('/check-business', [BusinessController::class, 'check_business'])->name('check_business');
    Route::post('/delete-old-business/{old_business_id}', [BusinessController::class, 'delete_old_business'])->name('delete_old_business');

    Route::get('subscriptions', [SubscriptionController::class, 'subscriptions'])->name('subscriptions');
    Route::get('/subscriptions/{id}/transactions',[SubscriptionController::class, 'view_transactions'])->name('view_transactions');
    Route::post('/subscriptions/filter', [SubscriptionController::class, 'subscriptions_filter'])->name('subscriptions_filter');

    // Category Count Routes
    Route::get('category-count', [BusinessController::class, 'category_count'])->name('category_count');
    Route::get('category/{category_id}/businesses', [BusinessController::class, 'category_businesses'])->name('category_businesses');
    Route::get('add-category', [CategoryController::class, 'category_create'])->name('category_create');
    Route::post('add-category-action', [CategoryController::class, 'category_store'])->name('category_store');
    Route::get('edit-category/{category_id}', [CategoryController::class, 'category_edit'])->name('category_edit');
    Route::post('edit-category-action/{category_id}', [CategoryController::class, 'category_update'])->name('category_update');
    Route::get('/category-check-name', [CategoryController::class, 'category_check_name'])->name('category_check_name');

    // Notification Routes
    Route::get('notifications', [NotificationController::class, 'notifications'])->name('notifications');
    Route::get('/notification-details', [NotificationController::class, 'notification_details'])->name('notification_details');
    Route::get('notification-queue', [NotificationController::class, 'notification_queue'])->name('notification_queue');
    Route::post('/notification-resend/{business_id}', [NotificationController::class, 'notification_resend'])->name('notification_resend');
    Route::post('/notification-pause-toggle/{business_id}', [NotificationController::class, 'notification_pause_toggle'])->name('notification_pause_toggle');

    // Email template master (shared by WWD Admin, WWD Business and WWD Listing)
    Route::get('email-templates', [EmailTemplateController::class, 'email_templates'])->name('email_templates');
    Route::get('add-email-template', [EmailTemplateController::class, 'email_template_create'])->name('email_template_create');
    Route::post('add-email-template-action', [EmailTemplateController::class, 'email_template_store'])->name('email_template_store');
    Route::get('edit-email-template/{id}', [EmailTemplateController::class, 'edit_email_template'])->name('edit_email_template');
    Route::post('edit-email-template-action/{id}', [EmailTemplateController::class, 'edit_email_template_action'])->name('edit_email_template_action');
    Route::post('/delete-email-template/{id}', [EmailTemplateController::class, 'delete_email_template'])->name('delete_email_template');
    Route::post('/email-template-preview', [EmailTemplateController::class, 'email_template_preview'])->name('email_template_preview');
    Route::post('/email-template-test', [EmailTemplateController::class, 'email_template_test'])->name('email_template_test');
    Route::get('email-layout', [EmailTemplateController::class, 'email_layout'])->name('email_layout');
    Route::post('email-layout-action', [EmailTemplateController::class, 'email_layout_action'])->name('email_layout_action');
    Route::post('/email-layout-preview', [EmailTemplateController::class, 'email_layout_preview'])->name('email_layout_preview');

    // Settings for the public directory site (social links, WWD's Google reviews): table site_settings
    Route::get('settings', [SettingController::class, 'settings'])->name('settings');
    Route::post('settings-action', [SettingController::class, 'settings_action'])->name('settings_action');
    Route::post('/settings-google-test', [SettingController::class, 'settings_google_test'])->middleware('throttle:10,1')->name('settings_google_test');

    // Home banners: photos that rotate at the top of the public home page. Table site_banners, files in wwdbucket/banner/
    Route::get('banners', [BannerController::class, 'banners'])->name('banners');
    Route::get('add-banner', [BannerController::class, 'banner_create'])->name('banner_create');
    Route::post('add-banner-action', [BannerController::class, 'banner_store'])->name('banner_store');
    Route::get('edit-banner/{id}', [BannerController::class, 'edit_banner'])->name('edit_banner');
    Route::post('edit-banner-action/{id}', [BannerController::class, 'edit_banner_action'])->name('edit_banner_action');
    Route::post('/banner-status/{id}', [BannerController::class, 'banner_status'])->name('banner_status');
    Route::post('/delete-banner/{id}', [BannerController::class, 'delete_banner'])->name('delete_banner');
});

// Public email click tracking (no login): records the click, then forwards to the email's real link
Route::get('/t/{token}', [NotificationController::class, 'email_click'])
    ->where('token', '[A-Za-z0-9]{40}')
    ->middleware('throttle:60,1')
    ->name('email_click');

// Public unsubscribe (no login, signed link from the email footer). GET only shows a confirm button, so mail
// scanners that open links can't unsubscribe anyone; the POST does it.
Route::match(['get', 'post'], '/unsubscribe/{business_id}', [NotificationController::class, 'email_unsubscribe'])
    ->whereNumber('business_id')
    ->middleware('throttle:30,1')
    ->name('email_unsubscribe');

Route::group(['middleware' => 'guest'], function () {
    Route::get('/', [AdminAuthController::class, 'admin_login'])->name('login');
    Route::post('/admin-login-action', [AdminAuthController::class, 'admin_login_action'])->name('admin.login.action');
});

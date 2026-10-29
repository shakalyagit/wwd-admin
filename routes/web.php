<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NotificationController;
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

    Route::get('/business/create', [BusinessController::class, 'business_create'])->name('business_create');
    Route::post('/business/store', [BusinessController::class, 'business_store'])->name('business_store');
    Route::post('/business/address', [BusinessController::class, 'business_address'])->name('business_address');
    Route::post('/business/logo', [BusinessController::class, 'business_logo'])->name('business_logo');

    Route::get('/users', [UserController::class, 'users'])->name('users');
    Route::post('/users-status-update', [UserController::class, 'users_status_update'])->name('users_status_update');

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
    Route::get('/category-check-name', [CategoryController::class, 'category_check_name'])->name('category_check_name');

    // Notification Routes
    Route::get('notifications', [NotificationController::class, 'notifications'])->name('notifications');
    Route::get('/notifications-filter', [NotificationController::class, 'notifications_filter'])->name('notifications_filter');
    Route::get('notification-queue', [NotificationController::class, 'notification_queue'])->name('notification_queue');
    Route::post('/notification-resend/{business_id}', [NotificationController::class, 'notification_resend'])->name('notification_resend');
    Route::post('/notification-pause-toggle/{business_id}', [NotificationController::class, 'notification_pause_toggle'])->name('notification_pause_toggle');
    Route::get('notification-templates', [NotificationController::class, 'notification_templates'])->name('notification_templates');
    Route::get('edit-notification-template/{id}', [NotificationController::class, 'edit_notification_template'])->name('edit_notification_template');
    Route::post('edit-notification-template-action/{id}', [NotificationController::class, 'edit_notification_template_action'])->name('edit_notification_template_action');
});

Route::group(['middleware' => 'guest'], function () {
    Route::get('/', [AdminAuthController::class, 'admin_login'])->name('login');
    Route::post('/admin-login-action', [AdminAuthController::class, 'admin_login_action'])->name('admin.login.action');
});

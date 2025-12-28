<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\RiskRegisterController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth'], function () {
    Route::get('dashboard', [AdminAuthController::class, 'dashboard'])->name('dashboard');
    Route::get('logout', [AdminAuthController::class, 'logout'])->name('logout');
    Route::post('change-password', [AdminAuthController::class, 'change_password'])->name('change_password');
    Route::post('update-profile', [AdminAuthController::class, 'update_profile'])->name('update_profile');

    //Business Listing Route
    Route::get('business-list', [BusinessController::class, 'business_list'])->name('business_list');
    Route::get('edit-business/{business_id}', [BusinessController::class, 'edit_business'])->name('edit_business');
    Route::post('edit-business-action/{business_id}', [BusinessController::class, 'edit_business_action'])->name('edit_business_action');
    Route::post('edit-business-address-action/{business_id}', [BusinessController::class, 'edit_business_address_action'])->name('edit_business_address_action');
    Route::post('edit-business-hours-action/{business_id}', [BusinessController::class, 'edit_business_hours_action'])->name('edit_business_hours_action');
    Route::post('edit-user-action/{business_id}', [BusinessController::class, 'edit_user_action'])->name('edit_user_action');
    Route::post('/business/{business}/approve', [BusinessController::class, 'business_approve'])->name('business_approve');
    Route::post('/business/{business}/reject', [BusinessController::class, 'business_reject'])->name('business_reject');
    Route::get('/business-list-filter', [BusinessController::class, 'business_list_filter'])->name('business_list_filter');

    //Old Business Listing Route
    Route::get('old-business-list', [BusinessController::class, 'old_business_list'])->name('old_business_list');
    Route::get('edit-old-business/{old_business_id}', [BusinessController::class, 'edit_old_business'])->name('edit_old_business');
    Route::post('/old-business-action/{old_business_id}', [BusinessController::class, 'old_business_action'])->name('old_business_action');
    Route::post('/check-business', [BusinessController::class, 'check_business'])->name('check_business');
    Route::post('/delete-old-business/{old_business_id}', [BusinessController::class, 'delete_old_business'])->name('delete_old_business');
});

Route::group(['middleware' => 'guest'], function () {
    Route::get('/', [AdminAuthController::class, 'admin_login'])->name('login');
    Route::post('/admin-login-action', [AdminAuthController::class, 'admin_login_action'])->name('admin.login.action');
});

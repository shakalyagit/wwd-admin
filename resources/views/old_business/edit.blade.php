@extends('layouts.main')
@section('content')
<style>
    textarea.form-control {
        height: auto !important;
    }
</style>

<div class="content-header">
    <h5 class="pull-left">Edit business - <span class="text-primary">{{ $edit_old_business->caption }}</span></h5>
    <div class="ms-auto pull-right d-flex gap-2">
        <a href="{{$edit_old_business->url}}" class="btn btn-outline-warning" target="_blank"><i class="bi bi-eye"></i> View website</a>
        <x-back-btn :url="url()->previous()" btn_class="btn-outline-primary" icon="bi-arrow-left" title="Back" />
    </div>
    <div class="clear"></div>
</div>
<x-flash />
<div id="flash-message"></div>
<form action="" method="POST" id="business_form"
    enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-300">
                    <h5 class="card-title">Business information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">Business name</label>
                                <input type="text" class="form-control" name="caption"
                                    value="{{ $edit_old_business->caption }}" id="caption">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">URL</label>
                                <input type="text" class="form-control" name="url"
                                    value="{{ $edit_old_business->display_url }}" id="url">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">Business Email</label>
                                <div class="input-group" style="display: -webkit-box;">
                                    <input type="text"
                                        class="form-control"
                                        name="email_username"
                                        value="{{ $edit_old_business->email_username }}"
                                        placeholder="username">
                                    <span class="input-group-text">
                                        {{ '@' . ($edit_old_business->email_domain ?? 'worldweb-directory.com') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <input type="text" class="form-control" name="category"
                                    value="{{ $edit_old_business->category_name }}" id="category">
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-group">
                                <label class="form-label">Business description</label>
                                <textarea class="form-control" name="site_desc" id="site_desc">{!! $edit_old_business->site_desc !!}</textarea>
                            </div>
                        </div>
                        <h5>Login information</h5>
                        <div class="col-md-6 mt-3 mb-3">
                            <div class="form-group">
                                <label class="form-label">First name</label>
                                <input type="text" class="form-control" name="first_name"
                                    value="{{ $edit_old_business->first_name }}" id="first_name">
                            </div>
                        </div>
                        <div class="col-md-6 mt-3 mb-3">
                            <div class="form-group">
                                <label class="form-label">Last name</label>
                                <input type="text" class="form-control" name="last_name"
                                    value="{{ $edit_old_business->last_name }}" id="last_name">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <div class="input-group" style="display: -webkit-box;">
                                    <input type="text"
                                        class="form-control"
                                        name="email_username"
                                        value="{{ $edit_old_business->email_username }}"
                                        placeholder="username">
                                    <span class="input-group-text">
                                        {{ '@' . ($edit_old_business->email_domain ?? 'worldweb-directory.com') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
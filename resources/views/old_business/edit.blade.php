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
        <a href="{{route('old_business_list')}}" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i>Back</a>
    </div>
    <div class="clear"></div>
</div>
<x-flash />
<div id="flash-message"></div>
<form action="{{route('old_business_action',Crypt::encrypt($edit_old_business->old_business_id))}}" method="POST" id="business_form"
    enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-300 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Business information</h5>

                    <button type="button"
                        class="btn btn-outline-primary"
                        id="check-business">
                        Check
                    </button>
                </div>

                <div class="card-body">
                    <input type="hidden" name="stime" value="{{ $edit_old_business->stime }}">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">Business name</label>
                                <input type="text" class="form-control" name="caption"
                                    value="{{ $edit_old_business->caption }}" id="caption">
                                <small class="text-danger d-none" id="caption-error"></small>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group mb-3">
                                <label class="form-label">URL</label>
                                <input type="text" class="form-control" name="url"
                                    value="{{ $edit_old_business->display_url }}" id="url">
                                <small class="text-danger d-none" id="url-error"></small>
                                <small class="text-success d-none" id="check-success"></small>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">Business Email</label>
                                <span class="input-group-text">
                                    {{ $edit_old_business->mail }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <span class="input-group-text">
                                    {{ $edit_old_business->category_name ?? 'N/A' }}
                                </span>
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
                                <label class="form-label">New Business Email</label>
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
                                <label class="form-label">New Business Category</label>
                                <select name="new_category_id" class="form-select" required>
                                    <option value="">Select category</option>
                                    @foreach($parents as $parent)
                                    <option value="{{ $parent->category_id }}">
                                        {{ $parent->cat_name }}
                                    </option>
                                    @foreach($children->where('parent_cat_id', $parent->category_id) as $child)
                                    <option value="{{ $child->category_id }}">
                                        {{ $parent->cat_name }} » {{ $child->cat_name }}
                                    </option>
                                    @endforeach
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <button type="submit" class="btn btn-primary">Update & Approve</button>
                            <a href="javascript:void(0);" onclick="return confirm('Are you sure you want to delete this?')" id="delete_old_business" class="btn btn-outline-danger ms-2"><i class="bi bi-trash"></i>Delete</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
@section('scripts')
<script>
    $(document).ready(function() {

        $('#check-business').on('click', function() {

            $('#caption-error').addClass('d-none');
            $('#url-error').addClass('d-none');
            $('#check-success').addClass('d-none');

            $.ajax({
                url: "{{ route('check_business') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    caption: $('#caption').val(),
                    url: $('#url').val()
                },
                success: function(res) {

                    if (res.status === 'error') {

                        if (res.errors.caption) {
                            $('#caption-error').text(res.errors.caption).removeClass('d-none');
                        }

                        if (res.errors.url) {
                            $('#url-error').text(res.errors.url).removeClass('d-none');
                        }

                    } else {
                        $('#check-success')
                            .text('Business name & URL are available.')
                            .removeClass('d-none');
                    }
                }
            });
        });

        $('#delete_old_business').click(function(e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('delete_old_business', $edit_old_business->old_business_id) }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                },
                success: function(res) {
                    if (res.status === 'success') {
                        window.location.href = "{{ route('old_business_list') }}";
                    }
                }
            });
        })

    });
</script>
@endsection
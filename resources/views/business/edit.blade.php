@extends('layouts.main')
@section('content')
<style>
    textarea.form-control {
        height: auto !important;
    }
</style>
@if($edit_business->file_path != null)
<div class="text-center mt-3">
    <a href="{{ config('services.customer_app.url') }}{{ $edit_business->file_path }}" target="_blank">
        <img src="{{ config('services.customer_app.url') }}{{ $edit_business->file_path }}"
            class="img-fluid"
            style="max-width: 100px;">
    </a>
</div>
@endif
<div class="content-header">
    <h5 class="pull-left">Edit business - <span class="text-primary">{{ $edit_business->business_name }}</span></h5>
    <div class="ms-auto pull-right mr-2">
        <a href="{{route('business_list')}}" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i>Back</a>
    </div>
    <div class="d-flex justify-content-end gap-2">
        <a href="{{$edit_business->business_website}}" class="btn btn-outline-warning" target="_blank"><i class="bi bi-eye"></i> View website</a>
        <form method="POST"
            action="{{ route('business_approve', $edit_business->business_id) }}">
            @csrf
            <button type="submit" class="btn btn-success">
                Approve
            </button>
        </form>
        <form method="POST"
            action="{{ route('business_reject', $edit_business->business_id) }}">
            @csrf
            <button type="submit" class="btn btn-danger me-2">
                Reject
            </button>
        </form>
    </div>
    <div class="clear"></div>
</div>
<x-flash />
<div id="flash-message"></div>
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-300">
                <h5 class="card-title">Business information</h5>
            </div>
            <form action="{{ route('edit_business_action', $edit_business->business_id) }}" method="POST" id="business_form"
                enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="form-group">
                                <label class="form-label">Business Website</label>
                                <input type="text"
                                    class="form-control"
                                    name="business_website"
                                    value="{{ $edit_business->business_website }}"
                                    id="business_website">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="form-group">
                                <label class="form-label">Business name</label>
                                <input type="text" class="form-control" name="business_name" id="business_name" value="{{$edit_business->business_name}}" readonly>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-group">
                                <label class="form-label">Business description</label>
                                <textarea class="form-control" name="business_desc" id="business_desc">{!! $edit_business->business_desc !!}</textarea>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-group">
                                <label class="form-label">Business email</label>
                                <input type="text" class="form-control" name="business_email"
                                    value="{{ $edit_business->business_email }}" id="business_email" readonly>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">Country code</label>
                                <select name="country_code" id="country_code" class="form-select">
                                    <option value="">Select Country Code</option>
                                    @foreach($countries as $country)
                                    @if(isset($country['calling_code']) && $country['calling_code'])
                                    <option value="{{ '+'.$country['calling_code'] }}"
                                        {{ ($edit_business->user->country_code ?? $edit_business->country_code) == '+'.$country['calling_code'] ? 'selected' : '' }}>
                                        {{ $country['name'] }} (+{{ $country['calling_code'] }})
                                    </option>
                                    @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">Business phone</label>
                                <input type="text" class="form-control" name="business_phone"
                                    value="{{ $edit_business->business_phone }}" id="business_phone">
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <select name="category_id" id="" class="form-select">
                                    <option value="" disabled selected>Select category</option>
                                    @foreach($parents as $parent)
                                    <option value="{{ $parent->category_id }}" {{ $edit_business->category_id == $parent->category_id ? 'selected' : '' }}>{{ $parent->cat_name }}</option>
                                    @foreach($children->where('parent_cat_id', $parent->category_id) as $child)
                                    <option value="{{ $child->category_id }}" {{ $edit_business->category_id == $child->category_id ? 'selected' : '' }}> {{ $parent->cat_name }} » {{ $child->cat_name }}</option>
                                    @endforeach
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-group">
                                <label class="form-label">Facebook URL</label>
                                <input type="text" class="form-control" name="facebook_url"
                                    value="{{ $edit_business->facebook_url }}" id="facebook_url">
                            </div>
                            @error('facebook_url')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-group">
                                <label class="form-label">Instagram URL</label>
                                <input type="text" class="form-control" name="instragram_url"
                                    value="{{ $edit_business->instragram_url }}" id="instragram_url">
                            </div>
                            @error('instragram_url')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-group">
                                <label class="form-label">Youtube URL</label>
                                <input type="text" class="form-control" name="youtube_url"
                                    value="{{ $edit_business->youtube_url }}" id="youtube_url">
                            </div>
                            @error('youtube_url')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-group">
                                <label class="form-label">Twitter URL</label>
                                <input type="text" class="form-control" name="twitter_url"
                                    value="{{ $edit_business->twitter_url }}" id="twitter_url">
                            </div>
                            @error('twitter_url')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-group">
                                <label class="form-label">Linkedin URL</label>
                                <input type="text" class="form-control" name="linkedin_url"
                                    value="{{ $edit_business->linkedin_url }}" id="linkedin_url">
                            </div>
                            @error('linkedin_url')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12 mb-3">
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-300">
                <h5 class="card-title">Address information</h5>
            </div>
            <form action="{{ route('edit_business_address_action', $edit_business->business_id) }}" method="POST" id="update_address"
                enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="form-group">
                                <label class="form-label">Address Line 1</label>
                                <input type="text" class="form-control" name="street_line_1"
                                    value="{{ $business_address->street_line_1 ?? '' }}" id="street_line_1">
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-group">
                                <label class="form-label">Address Line 2</label>
                                <input type="text" class="form-control" name="street_line_2" id="street_line_2" value="{{$business_address->street_line_2 ?? ''}}">
                            </div>
                        </div>
                        <div class="col-md-6 md-3">
                            <div class="form-group">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city"
                                    value="{{ $business_address->city ?? '' }}" id="city">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">Province/State/Territory</label>
                                <input type="text" class="form-control" name="province_state_territory"
                                    value="{{ $business_address->province_state_territory ?? '' }}" id="province_state_territory">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">Postal code</label>
                                <input type="text" class="form-control" name="postal_code"
                                    value="{{ $business_address->postal_code ?? '' }}" id="postal_code">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">Country</label>
                                <select name="ref_country_id" id="ref_country_id" class="form-select">
                                    <option value="">Select Country</option>
                                    @foreach($ref_countries as $country)
                                    <option value="{{ $country->ref_countries_id }}"
                                        {{ $country->ref_countries_id == ($business_address?->ref_country_id) ? 'selected' : '' }}>
                                        {{ $country->printable_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card mb-3">
            <div class="card-header bg-300">
                <h5 class="card-title">User information</h5>
            </div>
            <form action="{{ route('edit_user_action', $edit_user->id) }}" method="POST" id="update_address"
                enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">First name</label>
                                <input type="text" class="form-control" name="first_name"
                                    value="{{ $edit_user->first_name }}" id="first_name">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">Last name</label>
                                <input type="text" class="form-control" name="last_name" id="last_name" value="{{$edit_user->last_name}}">
                            </div>
                        </div>
                        <div class="col-md-6 md-3">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="text" class="form-control" name="email"
                                    value="{{ $edit_user->email }}" id="email">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">Is active</label>
                                <select name="is_active" id="is_active" class="form-select">
                                    <option value="">Select</option>
                                    <option value="1" {{ $edit_user->is_active == 1 ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ $edit_user->is_active == 0 ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card">
            <div class="card-header bg-300">
                <h5 class="card-title">Other information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label class="form-label">Submission date</label>
                            <p>{{ date('d-m-Y', strtotime($edit_business->created_at)) }}</p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label class="form-label">Payment status</label>
                            {!! payment_badge($edit_business->business_status) !!}
                        </div>
                    </div>
                    <div class="col-md-6 md-3">
                        <div class="form-group">
                            <label class="form-label">Business verfied</label>
                            {!! status_badge($edit_business->is_claimed) !!}
                        </div>
                    </div>
                    <div class="col-md-6 md-3">
                        <div class="form-group">
                            <label class="form-label">Admin verfied</label>
                            {!! status_badge($edit_business->is_admin_verified) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 mt-3">
        <div class="card">
            <div class="card-header bg-300">
                <h5 class="card-title">Hours information</h5>
            </div>
            <form method="POST" action="{{route('edit_business_hours_action', $edit_business->business_id)}}" id="hours_form">
                @csrf
                <table class="table table-hover my-business">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Open Time</th>
                            <th>Close Time</th>
                            <th>Closed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $days = [
                        1 => 'Sunday',
                        2 => 'Monday',
                        3 => 'Tuesday',
                        4 => 'Wednesday',
                        5 => 'Thursday',
                        6 => 'Friday',
                        7 => 'Saturday'
                        ];
                        @endphp
                        @foreach ($days as $dayNumber => $dayName)
                        <tr>
                            <td>{{ $dayName }}</td>
                            <td>
                                <select name="start[{{ $dayNumber }}]" class="form-select">
                                    <option value="">Select Time</option>
                                    @foreach ($times as $time)
                                    <option value="{{ $time }}"
                                        {{ isset($hours[$dayNumber]) && \Carbon\Carbon::parse($hours[$dayNumber]->start_ts)->format('h:i A') == $time ? 'selected' : '' }}>
                                        {{ $time }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="end[{{ $dayNumber }}]" class="form-select">
                                    <option value="">Select Time</option>
                                    @foreach ($times as $time)
                                    <option value="{{ $time }}"
                                        {{ isset($hours[$dayNumber]) && \Carbon\Carbon::parse($hours[$dayNumber]->end_ts)->format('h:i A') == $time ? 'selected' : '' }}>
                                        {{ $time }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="checkbox"
                                    name="closed[{{ $dayNumber }}]"
                                    class="form-check-input day-closed"
                                    data-day="{{ $dayNumber }}"
                                    {{ isset($hours[$dayNumber]) && $hours[$dayNumber]->is_closed ? 'checked' : '' }}>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="col-md-12 mb-3 ms-3">
                    <button type="submit" class="btn btn-primary">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('scripts')

@endsection
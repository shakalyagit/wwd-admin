@extends('layouts.main')
@section('content')
@php
    $status_badges = [
        'live'      => ['badge-subtle-success', 'Showing'],
        'scheduled' => ['badge-subtle-info', 'Scheduled'],
        'ended'     => ['badge-subtle-dark', 'Ended'],
        'off'       => ['badge-subtle-dark', 'Off'],
    ];
@endphp
<div class="content-header">
    <div>
        <h5>Home banners</h5>
        <p class="page-subtitle">Photos that rotate at the top of the worldweb-directory.com home page, in this order. A banner shows while it is on and within its dates. The site picks up changes within 10 minutes.</p>
    </div>
    @unless ($table_missing)
    <a href="{{ route('banner_create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New banner</a>
    @endunless
</div>
<x-flash />

@if ($table_missing)
    <div class="alert alert-warning">
        The <span class="font-monospace">site_banners</span> table doesn't exist yet. Run
        <span class="font-monospace">2026_11_15_create_site_banners.sql</span> (from the directory site's release) in phpMyAdmin first.
    </div>
@else
    @if ($live_count === 0)
        <div class="alert alert-info">
            No banner is showing right now, so the home page uses its plain dark top section. Add a banner, or switch one on.
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <span class="users-count">{{ $live_count }} of {{ $banners->count() }} {{ Str::plural('banner', $banners->count()) }} showing on the home page</span>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 banner-table">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Headline</th>
                        <th>Button and credit</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th class="text-end">Order</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($banners as $banner)
                    @php
                        $status = $banner->status();
                        $edit_url = route('edit_banner', encrypt($banner->banner_id));
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ $edit_url }}" class="banner-thumb">
                                <img src="{{ banner_image_url($banner->image_file) }}" alt="" loading="lazy" width="160" height="64" class="focus-{{ $banner->focus }}">
                            </a>
                        </td>
                        <td>
                            <a href="{{ $edit_url }}" class="d-block fw-semi-bold">{{ $banner->headline }}</a>
                            @if ($banner->subtext)<div class="user-sub">{{ Str::limit($banner->subtext, 80) }}</div>@endif
                        </td>
                        <td class="fs-10">
                            @if ($banner->button_text)
                                <div><i class="bi bi-box-arrow-up-right text-600"></i> {{ $banner->button_text }}</div>
                            @endif
                            @if ($banner->credit_text)
                                <div class="text-600"><i class="bi bi-camera"></i> {{ $banner->credit_text }}</div>
                            @endif
                            @if (!$banner->button_text && !$banner->credit_text)<span class="text-600">–</span>@endif
                        </td>
                        <td class="fs-10 text-nowrap">
                            @if ($banner->starts_on || $banner->ends_on)
                                {{ $banner->starts_on ? $banner->starts_on->format('j M Y') : 'Now' }} – {{ $banner->ends_on ? $banner->ends_on->format('j M Y') : 'no end' }}
                            @else
                                <span class="text-600">Always</span>
                            @endif
                        </td>
                        <td><span class="badge rounded-pill {{ $status_badges[$status][0] }}">{{ $status_badges[$status][1] }}</span></td>
                        <td class="text-end font-monospace">{{ $banner->sort_order }}</td>
                        <td class="text-nowrap text-end">
                            <form action="{{ route('banner_status', encrypt($banner->banner_id)) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-light btn-sm" title="{{ $banner->is_active ? 'Switch off' : 'Switch on' }}">
                                    <i class="bi {{ $banner->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i> {{ $banner->is_active ? 'Switch off' : 'Switch on' }}
                                </button>
                            </form>
                            <a class="btn btn-light btn-sm" href="{{ $edit_url }}" title="Edit banner"><i class="bi bi-pencil"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-600"><i class="bi bi-images d-block fs-6 mb-2"></i>No banners yet. Add the first photo for the home page.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection

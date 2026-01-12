@extends('layouts.main')
@section('content')
<style>
    textarea.form-control {
        height: auto !important;
    }
</style>
<div class="content-header">
    <div class="clear"></div>
</div>
<div id="flash-message"></div>
<div class="row">
    <div class="col-md-12">
        <div class="card mb-3">
            <div class="card-header bg-300 d-flex justify-content-between align-items-center">
                <h5 class="card-title">Add business</h5>
                <a href="{{route('business_list')}}" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i>Back</a>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" role="tablist">

                    {{-- Business --}}
                    <li class="nav-item">
                        <button class="nav-link {{ $activeTab == 'business' ? 'active' : '' }}"
                            data-bs-toggle="tab"
                            data-bs-target="#business"
                            type="button">
                            Business Details
                        </button>
                    </li>

                    {{-- Address --}}
                    <li class="nav-item">
                        <button class="nav-link
            {{ $step < 2 ? 'disabled' : '' }}
            {{ $activeTab == 'address' ? 'active' : '' }}"
                            data-bs-toggle="tab"
                            data-bs-target="#address"
                            type="button"
                            {{ $step < 2 ? 'disabled' : '' }}>
                            Business Address
                        </button>
                    </li>

                    {{-- Logo --}}
                    <li class="nav-item">
                        <button class="nav-link
            {{ $step < 2 ? 'disabled' : '' }}
            {{ $activeTab == 'logo' ? 'active' : '' }}"
                            data-bs-toggle="tab"
                            data-bs-target="#logo"
                            type="button"
                            {{ $step < 2 ? 'disabled' : '' }}>
                            Business Logo
                        </button>
                    </li>

                </ul>


                <div class="tab-content border border-top-0 p-3">

                    <div class="tab-pane fade {{ $activeTab == 'business' ? 'show active' : '' }}" id="business">
                        @include('business.partials.business-details')
                    </div>

                    <div class="tab-pane fade {{ $activeTab == 'address' ? 'show active' : '' }}" id="address">
                        @include('business.partials.business-address')
                    </div>

                    <div class="tab-pane fade {{ $activeTab == 'logo' ? 'show active' : '' }}" id="logo">
                        @include('business.partials.business-logo')
                    </div>

                </div>

            </div>
        </div>
    </div>

</div>
@endsection
@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        let step = {
            {
                session('step', 1)
            }
        };

        if (step === 2) {
            new bootstrap.Tab(document.querySelector('#address-tab')).show();
        }

        if (step === 3) {
            new bootstrap.Tab(document.querySelector('#logo-tab')).show();
        }
    });
</script>

@endsection
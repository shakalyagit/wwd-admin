@extends('layouts.main')
@section('content')
<x-flash />
<div class="container">
    <div class="row mt-3">
        <div class="col-md-4">
            <div class="card mb-3 overflow-hidden" style="min-width: 12rem">
                <div class="bg-holder bg-card" style="background-image: url(assets/img/icons/spot-illustrations/corner-1.png)"></div>
                <div class="card-body position-relative">
                    <h6>
                        Total Business
                    </h6>
                    <div class="display-4 fs-4 mb-2 font-weight-normal text-sans-serif text-primary">
                        {{$total_business}}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3 overflow-hidden" style="min-width: 12rem">
                <div class="bg-holder bg-card" style="background-image: url(assets/img/icons/spot-illustrations/corner-2.png)"></div>
                <div class="card-body position-relative">
                    <h6>Verified Business</h6>
                    <div class="display-4 fs-4 mb-2 font-weight-normal text-sans-serif text-info">
                        {{$verified_business}}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3 overflow-hidden" style="min-width: 12rem">
                <div class="bg-holder bg-card" style="background-image: url(assets/img/icons/spot-illustrations/corner-3.png)"></div>
                <div class="card-body position-relative">
                    <h6>
                        Unverified Business
                    </h6>
                    <div class="display-4 fs-4 mb-2 font-weight-normal text-sans-serif">{{$unverified_business}}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3 overflow-hidden" style="min-width: 12rem">
                <div class="bg-holder bg-card" style="background-image: url(assets/img/icons/spot-illustrations/corner-4.png)"></div>
                <div class="card-body position-relative">
                    <h6>
                        Todays Business
                    </h6>
                    <div class="display-4 fs-4 mb-2 font-weight-normal text-sans-serif text-info">
                        {{$todays_business}}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3 overflow-hidden" style="min-width: 12rem">
                <div class="bg-holder bg-card" style="background-image: url(assets/img/icons/spot-illustrations/corner-5.png)"></div>
                <div class="card-body position-relative">
                    <h6>Approved Business</h6>
                    <div class="display-4 fs-4 mb-2 font-weight-normal text-sans-serif text-warning">
                        {{$approved_business}}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3 overflow-hidden" style="min-width: 12rem">
                <div class="bg-holder bg-card" style="background-image: url(assets/img/icons/spot-illustrations/corner-6.png)"></div>
                <div class="card-body position-relative">
                    <h6>
                        Featured Business
                    </h6>
                    <div class="display-4 fs-4 mb-2 font-weight-normal text-sans-serif text-success">{{$featured_business}}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
        Last 10 Days Business Report
        <small class="text-muted">
            ({{ $start_date->format('d M Y') }} – {{ $end_date->format('d M Y') }})
        </small>
    </h5>
        </div>
        <div class="card-body">
            <div id="business_chart" style="height: 400px;"></div>
        </div>
    </div>

</div>
<div style="clear: both"></div>
@endsection
<script src="https://cdn.jsdelivr.net/npm/echarts/dist/echarts.min.js"></script>
@section('scripts')
<script>
    var chartDom = document.getElementById('business_chart');
    var myChart = echarts.init(chartDom);

    var option = {
        tooltip: {
            trigger: 'item'
        },
        legend: {
            data: ['Total Submissions', 'Paid Submissions']
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '3%',
            containLabel: true
        },
        xAxis: {
            type: 'category',
            data: @json($dates)
        },
        yAxis: {
            type: 'value'
        },
        series: [{
                name: 'Total Submissions',
                type: 'bar',
                data: @json($total_submissions),
                barGap: '30%',
                barWidth: 35,
            },
            {
                name: 'Paid Submissions',
                type: 'bar',
                data: @json($paid_submissions),
                barGap: '30%',
                barWidth: 35,
            }
        ]
    };

    myChart.setOption(option);
</script>

@endsection
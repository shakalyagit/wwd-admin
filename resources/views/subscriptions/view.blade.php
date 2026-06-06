@extends('layouts.main')
@section('content')
<div class="content-header">
    <h5 class="pull-left"></h5>
    <a href="{{route('subscriptions')}}" class="btn btn-outline-primary" style="float: inline-end;"><i class="bi bi-arrow-left"></i> Back</a>
    <div class="clear"></div>
</div>
<x-flash />
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-1">Subscription Details</h5>
        <p class="mb-0"><strong>Subscription ID:</strong> {{ $subscription->paypal_subscription_id   }}</p>
        <p class="mb-0"><strong>Business:</strong> {{ $subscription->business_name }}</p>
        <p class="mb-0"><strong>Status:</strong> {!! $subscription->status !!}</p>
        <p class="mb-0"><strong>Start Date:</strong> {{ $subscription->subscription_start_date }}</p>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">Transactions</h5>
        <span class="fs-9">
            Total Paid: {{ number_format($total_amount, 2) }} {{ $transactions->first()->currency ?? '' }}
        </span>
    </div>

    <div class="card-body p-0">
        <table class="table mb-0">
            <thead class="bg-200">
                <tr>
                    <th>Date</th>
                    <th>Transaction ID</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                <tr>
                    <td>{{ date('d-m-Y', strtotime($tx->payment_date)) }}</td>
                    <td class="text-nowrap">{{ $tx->paypal_transaction_id }}</td>
                    <td>{{ number_format($tx->amount, 2) }} {{ $tx->currency }}</td>
                    <td>
                        <span class="badge bg-{{ $tx->status === 'completed' ? 'success' : 'warning' }}">
                            {{ ucfirst($tx->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center">
                        No transactions found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
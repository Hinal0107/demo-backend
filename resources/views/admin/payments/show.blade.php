@extends('layouts.admin')

@section('title', 'Payment #' . $payment->id . ' - Gateway Audit Log')

@section('content')
    <div class="header-section">
        <div>
            <a href="{{ route('admin.payments.index') }}" style="color: var(--accent-primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Back to Payments</a>
            <h1 class="page-title" style="margin-top: 10px;">Payment Transaction Details</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">Payment Record ID: #{{ $payment->id }} &bull; Gateway Reference: {{ $payment->worldpay_reference ?: 'N/A' }}</p>
        </div>
    </div>

    <div style="display: flex; gap: 30px; margin-bottom: 40px; flex-wrap: wrap;">
        
        <!-- Summary details card -->
        <div style="flex: 2; min-width: 320px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; backdrop-filter: blur(10px);">
            <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Transaction Metrics</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px;">
                <div>
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Customer</span>
                    <span style="font-weight: 600;">{{ $payment->customer?->name }}</span>
                </div>
                <div>
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Restaurant</span>
                    <span style="font-weight: 600;">{{ $payment->restaurant?->name }}</span>
                </div>
                <div>
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Amount Paid</span>
                    <span style="font-weight: 700; color: var(--success); font-size: 16px;">£{{ number_format($payment->amount, 2) }}</span>
                </div>
                <div>
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Worldpay Transaction ID</span>
                    <span style="font-family: monospace;">{{ $payment->worldpay_transaction_id ?: 'N/A' }}</span>
                </div>
                <div>
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Payment Status</span>
                    @if($payment->status === 'PAID')
                        <span class="badge badge-success">Paid</span>
                    @elseif($payment->status === 'REFUNDED')
                        <span class="badge badge-info">Refunded</span>
                    @else
                        <span class="badge badge-danger">{{ $payment->status }}</span>
                    @endif
                </div>
                <div>
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Timestamps</span>
                    <span>Created: {{ $payment->created_at?->toDateTimeString() }}</span>
                    @if($payment->paid_at)
                        <span style="display: block; color: var(--success);">Paid: {{ $payment->paid_at->toDateTimeString() }}</span>
                    @endif
                </div>
            </div>

            @if($payment->status === 'REFUNDED')
                <div style="margin-top: 25px; border-top: 1px solid var(--border-color); padding-top: 20px; font-size: 14px;">
                    <h3 style="font-size: 15px; margin-bottom: 12px; color: var(--danger);">Refund Details</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Refund Amount</span>
                            <span style="font-weight: 700;">£{{ number_format($payment->refund_amount, 2) }}</span>
                        </div>
                        <div>
                            <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Refund Date</span>
                            <span>{{ $payment->refunded_at?->toDateTimeString() }}</span>
                        </div>
                        <div style="grid-column: span 2;">
                            <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Refund Reason</span>
                            <span>"{{ $payment->refund_reason }}"</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Raw Gateway Response -->
        <div style="flex: 1; min-width: 280px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; backdrop-filter: blur(10px);">
            <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Raw Webhook Log</h2>
            <pre style="background: rgba(15, 23, 42, 0.8); border: 1px solid var(--border-color); padding: 15px; border-radius: 8px; font-family: monospace; font-size: 11px; overflow-x: auto; color: var(--text-secondary); max-height: 350px;">{{ json_encode($payment->gateway_response, JSON_PRETTY_PRINT) ?: 'No logs available.' }}</pre>
        </div>
    </div>
@endsection

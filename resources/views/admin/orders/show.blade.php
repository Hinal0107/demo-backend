@extends('layouts.admin')

@section('title', 'Order #' . $order->order_number . ' Details')

@section('content')
    <div class="header-section">
        <div>
            <a href="{{ route('admin.orders.index') }}" style="color: var(--accent-primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Back to Orders</a>
            <h1 class="page-title" style="margin-top: 10px; font-family: monospace;">Order #{{ $order->order_number }}</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">Placed: {{ $order->created_at?->toDateTimeString() }}</p>
        </div>
    </div>

    <!-- Main Grid -->
    <div style="display: flex; gap: 30px; margin-bottom: 40px; flex-wrap: wrap;">
        
        <!-- Summary details card -->
        <div style="flex: 2; min-width: 320px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; backdrop-filter: blur(10px);">
            <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Order Details</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px; margin-bottom: 25px;">
                <div>
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Customer</span>
                    <span style="font-weight: 600;">{{ $order->customer?->name }}</span>
                    <span style="color: var(--text-secondary); display: block; font-size: 13px;">{{ $order->customer?->phone }}</span>
                </div>
                <div>
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Restaurant Scoped</span>
                    <span style="font-weight: 600;">{{ $order->restaurant?->name }}</span>
                    <span style="color: var(--text-secondary); display: block; font-size: 13px;">{{ $order->restaurant?->phone }}</span>
                </div>
                <div style="grid-column: span 2;">
                    <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Delivery Destination Address</span>
                    <span>{{ $order->address?->name }} ({{ $order->address?->phone }}) &bull; {{ $order->address?->address_line_1 }}, {{ $order->address?->city }}</span>
                </div>
                @if($order->notes)
                    <div style="grid-column: span 2;">
                        <span style="color: var(--text-secondary); display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;">Customer Notes</span>
                        <span>"{{ $order->notes }}"</span>
                    </div>
                @endif
            </div>

            <!-- Items list inside order -->
            <h3 style="font-size: 15px; margin-bottom: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 5px;">Order Items</h3>
            <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
                @foreach($order->items as $item)
                    <div style="display: flex; justify-content: space-between; font-size: 14px; padding: 10px 0; border-bottom: 1px dashed var(--border-color);">
                        <span>{{ $item->quantity }}x {{ $item->item_name }}</span>
                        <span style="font-weight: 600;">£{{ number_format($item->total_price, 2) }}</span>
                    </div>
                @endforeach
            </div>

            <!-- Total Cost Summary -->
            <div style="font-size: 14px; display: flex; flex-direction: column; gap: 8px; align-items: flex-end; border-top: 1px solid var(--border-color); padding-top: 15px;">
                <div>Subtotal: <span style="font-weight: 600; margin-left: 10px;">£{{ number_format($order->subtotal, 2) }}</span></div>
                <div>Tax (10%): <span style="font-weight: 600; margin-left: 10px;">£{{ number_format($order->tax, 2) }}</span></div>
                <div>Delivery Fee: <span style="font-weight: 600; margin-left: 10px;">£{{ number_format($order->delivery_fee, 2) }}</span></div>
                <div style="font-size: 18px; font-weight: 700; color: var(--success); margin-top: 5px;">Total Amount: <span style="margin-left: 10px;">£{{ number_format($order->total_amount, 2) }}</span></div>
            </div>
        </div>

        <!-- Milestones status card -->
        <div style="flex: 1; min-width: 250px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; backdrop-filter: blur(10px); display: flex; flex-direction: column; gap: 20px;">
            <div>
                <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Order Milestones</h2>
                
                <div style="display: flex; flex-direction: column; gap: 15px; font-size: 14px;">
                    <div>
                        <span style="color: var(--text-secondary); display: block; font-size: 11px; font-weight: 600; text-transform: uppercase;">Order Status</span>
                        @if($order->order_status === 'COMPLETED')
                            <span class="badge badge-success">Completed</span>
                        @elseif($order->order_status === 'CANCELLED')
                            <span class="badge badge-danger">Cancelled</span>
                        @else
                            <span class="badge badge-warning">{{ $order->order_status }}</span>
                        @endif
                    </div>

                    <div>
                        <span style="color: var(--text-secondary); display: block; font-size: 11px; font-weight: 600; text-transform: uppercase;">Delivery Status</span>
                        @if($order->delivery_status === 'DELIVERED')
                            <span class="badge badge-success">Delivered</span>
                        @elseif($order->delivery_status === 'OUT_FOR_DELIVERY')
                            <span class="badge badge-info">Out For Delivery</span>
                        @elseif($order->delivery_status === 'FAILED')
                            <span class="badge badge-danger">Failed</span>
                        @else
                            <span class="badge badge-secondary" style="color: var(--text-secondary); border: 1px solid var(--border-color);">Pending</span>
                        @endif
                    </div>

                    <div>
                        <span style="color: var(--text-secondary); display: block; font-size: 11px; font-weight: 600; text-transform: uppercase;">Payment Status</span>
                        @if($order->payment_status === 'PAID')
                            <span class="badge badge-success">Paid</span>
                        @elseif($order->payment_status === 'REFUNDED')
                            <span class="badge badge-info">Refunded</span>
                        @else
                            <span class="badge badge-danger">{{ $order->payment_status }}</span>
                        @endif
                    </div>

                    @if($order->delivery_otp)
                        <div>
                            <span style="color: var(--text-secondary); display: block; font-size: 11px; font-weight: 600; text-transform: uppercase;">Delivery Handover OTP</span>
                            <span style="font-size: 16px; font-weight: 700; letter-spacing: 2px; color: var(--info);">{{ $order->delivery_otp }}</span>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Quick Refund Action Form -->
            @if($order->payment_status === 'PAID')
                <div style="border-top: 1px solid var(--border-color); padding-top: 20px;">
                    <h3 style="font-size: 14px; margin-bottom: 12px; font-weight: 600;">Issue Refund</h3>
                    <form action="{{ route('admin.orders.refund', $order->id) }}" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                        @csrf
                        <input type="number" step="0.01" name="refund_amount" class="form-control" placeholder="Refund Amount (£)" max="{{ $order->total_amount }}" required>
                        <input type="text" name="reason" class="form-control" placeholder="Reason for refund" required>
                        <button type="submit" class="btn btn-danger" style="width: 100%; padding: 8px 15px; font-size: 13px;">Confirm Refund</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <!-- Order Status History Logs -->
    <div class="table-container">
        <div class="table-header">
            <h2 class="table-title">Status Transition Audit Trail</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Old Status</th>
                    <th>New Status</th>
                    <th>Changed By Role</th>
                    <th>Changer Name</th>
                    <th>Remarks</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->statusHistories as $history)
                    <tr>
                        <td>
                            <span class="badge @if($history->status_type === 'ORDER') badge-info @elseif($history->status_type === 'PAYMENT') badge-success @else badge-warning @endif">
                                {{ $history->status_type }}
                            </span>
                        </td>
                        <td><span style="color: var(--text-secondary);">{{ $history->old_status ?: 'INIT' }}</span></td>
                        <td style="font-weight: 600;">{{ $history->new_status }}</td>
                        <td>{{ $history->changed_by_role }}</td>
                        <td>{{ $history->changer?->name ?: 'System Job' }}</td>
                        <td>{{ $history->remarks ?: 'No remarks' }}</td>
                        <td>{{ $history->created_at?->toDateTimeString() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection

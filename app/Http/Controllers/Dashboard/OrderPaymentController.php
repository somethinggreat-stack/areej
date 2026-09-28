<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Manually update when the money came in" — one row per payment, so a deposit
 * on booking and a balance on the day are both visible with their dates.
 */
class OrderPaymentController extends Controller
{
    public function store(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', 'in:cash,bank_transfer,card,cheque,other'],
            'kind' => ['required', 'in:deposit,part_payment,balance,refund'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $order->payments()->create([
            ...$data,
            'amount' => (int) round((float) $data['amount'] * 100),
            'recorded_by' => $request->user()->id,
        ]);

        $order->refresh()->load('payments');

        $message = $order->isPaid()
            ? __('Payment recorded. This order is now paid in full.')
            : __('Payment recorded. :amount still outstanding.', [
                'amount' => '£'.number_format($order->balanceInPounds(), 2),
            ]);

        return back()->with('status', $message);
    }

    public function destroy(OrderPayment $payment): RedirectResponse
    {
        $payment->delete();

        return back()->with('status', __('Payment removed.'));
    }
}

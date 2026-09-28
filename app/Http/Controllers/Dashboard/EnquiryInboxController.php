<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\Order;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\User;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnquiryInboxController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        return view('dashboard.enquiries.index', [
            'enquiries' => Enquiry::with(['assignee', 'order'])
                ->when($status !== '', fn ($q) => $q->where('status', $status))
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'status' => $status,
            'counts' => [
                'new' => Enquiry::where('status', 'new')->count(),
                'contacted' => Enquiry::where('status', 'contacted')->count(),
                'quoted' => Enquiry::where('status', 'quoted')->count(),
                'won' => Enquiry::where('status', 'won')->count(),
                'lost' => Enquiry::where('status', 'lost')->count(),
            ],
        ]);
    }

    public function show(Enquiry $enquiry): View
    {
        return view('dashboard.enquiries.show', [
            'enquiry' => $enquiry->load(['assignee', 'order']),
            'team' => User::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Enquiry $enquiry): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,contacted,quoted,won,lost'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $enquiry->update($data);

        return back()->with('status', __('Enquiry updated.'));
    }

    public function destroy(Enquiry $enquiry): RedirectResponse
    {
        if ($enquiry->order !== null) {
            return back()->withErrors(['form' => __('This enquiry became a job, so it cannot be deleted.')]);
        }

        $reference = $enquiry->reference;
        $enquiry->delete();

        app(Activity::class)->deleted($enquiry, __('Enquiry :ref deleted', ['ref' => $reference]));

        return redirect()->route('enquiries')->with('status', __('Enquiry :ref deleted.', ['ref' => $reference]));
    }

    /**
     * Raise a quote from an enquiry, carrying across everything the customer
     * already told us. This is the normal path — converting straight to a job
     * skips the agreement on price.
     */
    public function quote(Request $request, Enquiry $enquiry): RedirectResponse
    {
        $quote = Quote::create([
            'enquiry_id' => $enquiry->id,
            'customer_name' => $enquiry->name,
            'phone' => $enquiry->phone,
            'email' => $enquiry->email,
            'event_type' => $enquiry->event_type,
            'event_date' => $enquiry->event_date,
            'venue' => $enquiry->venue,
            'guests' => $enquiry->guests ?: (int) Setting::get('minimum_guests', 20),
            'service_style' => $this->mapServiceStyle($enquiry->service_style),
            'status' => 'draft',
            'valid_until' => today()->addDays((int) Setting::get('quote_validity_days', 14)),
            'created_by' => $request->user()->id,
        ]);

        $enquiry->update(['status' => 'quoted']);

        app(Activity::class)->created($quote, __('Quote :ref raised from enquiry :enq', [
            'ref' => $quote->reference,
            'enq' => $enquiry->reference,
        ]));

        return redirect()->route('quotes.show', $quote)
            ->with('status', __('Quote :ref started. Add what they are having.', ['ref' => $quote->reference]));
    }

    /**
     * Turns an enquiry into a job, carrying across everything the customer
     * already told us so nobody retypes it.
     */
    public function convert(Request $request, Enquiry $enquiry): RedirectResponse
    {
        if ($enquiry->order !== null) {
            return redirect()->route('orders.show', $enquiry->order);
        }

        $order = Order::create([
            'enquiry_id' => $enquiry->id,
            'customer_name' => $enquiry->name,
            'phone' => $enquiry->phone,
            'email' => $enquiry->email,
            'order_type' => $enquiry->event_type,
            'event_date' => $enquiry->event_date ?? today()->addWeek(),
            'venue' => $enquiry->venue,
            'guests' => $enquiry->guests ?? 0,
            'service_style' => $this->mapServiceStyle($enquiry->service_style),
            'dietary' => $enquiry->dietary,
            'menu_notes' => $enquiry->message,
            'status' => 'draft',
            'source' => 'website',
            'taken_by' => $request->user()->id,
        ]);

        $enquiry->update(['status' => 'won']);

        return redirect()->route('orders.show', $order)
            ->with('status', __('Enquiry :ref converted to order :order.', [
                'ref' => $enquiry->reference,
                'order' => $order->reference,
            ]));
    }

    /**
     * The website form and the orders table use different vocabularies for the
     * same thing; this is the one place that has to know about both.
     */
    private function mapServiceStyle(?string $style): string
    {
        return match ($style) {
            'Delivery' => 'delivery',
            'Collection' => 'collection',
            'Delivered and served' => 'delivered_and_served',
            'Full buffet setup' => 'full_buffet',
            default => 'not_set',
        };
    }
}

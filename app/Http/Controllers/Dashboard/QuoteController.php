<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use App\Models\Order;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\Setting;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Quotations — the step that was missing between an enquiry and a job.
 *
 * A quote is its own record because it can be declined or expire without an
 * order ever existing, and the same customer is often quoted twice at different
 * guest numbers. Accepting one is the only thing that creates an order.
 */
class QuoteController extends Controller
{
    public function __construct(private readonly Activity $activity) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $quotes = Quote::query()
            ->with(['creator', 'order'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(fn ($q) => $q->where('customer_name', 'like', $term)
                    ->orWhere('reference', 'like', $term)
                    ->orWhere('venue', 'like', $term));
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $open = Quote::open()->get();

        return view('dashboard.quotes.index', [
            'quotes' => $quotes,
            'status' => $status,
            'counts' => [
                '' => Quote::count(),
                'draft' => Quote::where('status', 'draft')->count(),
                'sent' => Quote::where('status', 'sent')->count(),
                'accepted' => Quote::where('status', 'accepted')->count(),
                'declined' => Quote::where('status', 'declined')->count(),
            ],
            'openValue' => (int) $open->sum('total'),
            'acceptedThisMonth' => Quote::where('status', 'accepted')
                ->whereBetween('decided_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('total'),
            'winRate' => $this->winRate(),
        ]);
    }

    /**
     * Accepted as a share of everything that got a decision. Quotes still out
     * are excluded — counting them as losses would make every busy week look
     * like a bad one.
     */
    private function winRate(): ?int
    {
        $decided = Quote::whereIn('status', ['accepted', 'declined', 'expired'])->count();

        if ($decided === 0) {
            return null;
        }

        return (int) round(Quote::where('status', 'accepted')->count() / $decided * 100);
    }

    public function create(Request $request): View
    {
        return view('dashboard.quotes.form', [
            'quote' => new Quote([
                'event_date' => today()->addWeeks(2),
                'service_style' => 'not_set',
                'guests' => (int) Setting::get('minimum_guests', 20),
                'valid_until' => today()->addDays((int) Setting::get('quote_validity_days', 14)),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;
        $data['status'] = 'draft';

        $quote = Quote::create($data);

        $this->activity->created($quote, __('Quote :ref raised for :name', [
            'ref' => $quote->reference,
            'name' => $quote->customer_name,
        ]));

        return redirect()->route('quotes.show', $quote)
            ->with('status', __('Quote :ref created. Add what they are having.', ['ref' => $quote->reference]));
    }

    public function show(Quote $quote): View
    {
        return view('dashboard.quotes.show', [
            'quote' => $quote->load(['lines.dish', 'creator', 'enquiry', 'order']),
            'dishes' => Dish::active()->inMenuOrder()->get(),
        ]);
    }

    public function print(Quote $quote): View
    {
        return view('dashboard.quotes.print', [
            'quote' => $quote->load('lines.dish'),
        ]);
    }

    public function update(Request $request, Quote $quote): RedirectResponse
    {
        abort_unless($quote->isEditable(), 422, __('A decided quote cannot be edited.'));

        $before = $quote->getOriginal();
        $quote->update($this->validated($request));
        $quote->recalculate();

        $this->activity->updated($quote, __('Quote :ref updated', ['ref' => $quote->reference]), $before);

        return back()->with('status', __('Quote updated.'));
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        if ($quote->status === 'accepted') {
            return back()->withErrors(['form' => __('An accepted quote cannot be deleted — it is the record behind an order.')]);
        }

        $reference = $quote->reference;
        $quote->delete();

        $this->activity->deleted($quote, __('Quote :ref deleted', ['ref' => $reference]));

        return redirect()->route('quotes')->with('status', __('Quote :ref deleted.', ['ref' => $reference]));
    }

    /**
     * Add a line. Choosing a dish prices it per head from the menu and defaults
     * the quantity to the guest count, because that is the case nine times in
     * ten and re-typing it is where mistakes come from.
     */
    public function storeLine(Request $request, Quote $quote): RedirectResponse
    {
        abort_unless($quote->isEditable(), 422, __('A decided quote cannot be edited.'));

        $data = $request->validate([
            'dish_id' => ['nullable', 'exists:dishes,id'],
            'description' => ['required_without:dish_id', 'nullable', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'unit' => ['nullable', 'string', 'max:24'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:1000000'],
        ]);

        // validate() only returns keys the request actually sent, so every
        // optional field has to be read with a fallback rather than indexed.
        $dish = ! empty($data['dish_id']) ? Dish::find($data['dish_id']) : null;

        $quote->lines()->create([
            'dish_id' => $dish?->id,
            'description' => ($data['description'] ?? null) ?: $dish?->displayName(),
            'quantity' => $data['quantity'],
            'unit' => ($data['unit'] ?? null) ?: ($dish ? __('guests') : null),
            'unit_price' => (int) round((float) $data['unit_price'] * 100),
            'position' => (int) $quote->lines()->max('position') + 1,
        ]);

        $quote->recalculate();

        return back()->with('status', __('Line added.'));
    }

    public function destroyLine(QuoteLine $line): RedirectResponse
    {
        $quote = $line->quote;

        abort_unless($quote->isEditable(), 422, __('A decided quote cannot be edited.'));

        $line->delete();
        $quote->recalculate();

        return back()->with('status', __('Line removed.'));
    }

    public function send(Request $request, Quote $quote): RedirectResponse
    {
        if ($quote->lines()->count() === 0) {
            return back()->withErrors(['form' => __('Add at least one line before sending this quote.')]);
        }

        $quote->update([
            'status' => 'sent',
            'sent_at' => now(),
            'valid_until' => $quote->valid_until ?? today()->addDays((int) Setting::get('quote_validity_days', 14)),
        ]);

        $this->activity->log('sent', __('Quote :ref marked as sent', ['ref' => $quote->reference]), $quote);

        return back()->with('status', __('Quote marked as sent. It will be chased if there is no reply.'));
    }

    /**
     * Acceptance is the hinge of the whole system: it creates the job, carries
     * the priced lines onto it, and links the enquiry, quote and order together
     * so the trail from first contact to invoice stays unbroken.
     */
    public function accept(Request $request, Quote $quote): RedirectResponse
    {
        if ($quote->order_id !== null) {
            return redirect()->route('orders.show', $quote->order_id);
        }

        if ($quote->lines()->count() === 0) {
            return back()->withErrors(['form' => __('This quote has no lines to turn into a job.')]);
        }

        $order = DB::transaction(function () use ($quote, $request) {
            $order = Order::create([
                'enquiry_id' => $quote->enquiry_id,
                'customer_name' => $quote->customer_name,
                'phone' => $quote->phone,
                'email' => $quote->email,
                'order_type' => $quote->event_type,
                'event_date' => $quote->event_date ?? today()->addWeek(),
                'venue' => $quote->venue,
                'guests' => $quote->guests,
                'service_style' => $quote->service_style,
                'total_amount' => $quote->total,
                'deposit_due' => (int) round($quote->total * (int) Setting::get('deposit_percent', 25) / 100),
                'status' => 'confirmed',
                'source' => 'quote',
                'taken_by' => $request->user()->id,
                'staff_required' => (int) ceil(max(1, $quote->guests) / max(1, (int) Setting::get('staff_per_guests', 40))),
            ]);

            foreach ($quote->lines as $line) {
                $order->items()->create([
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit' => $line->unit,
                    'unit_price' => $line->unit_price,
                    'line_total' => $line->line_total,
                    'position' => $line->position,
                ]);

                // A dish line also tells the kitchen what to cook and for how
                // many, which is what drives the ingredient forecast.
                if ($line->dish_id !== null) {
                    $order->dishes()->firstOrCreate(
                        ['dish_id' => $line->dish_id],
                        ['guests' => (int) round((float) $line->quantity)]
                    );
                }
            }

            $quote->update([
                'status' => 'accepted',
                'decided_at' => now(),
                'order_id' => $order->id,
            ]);

            $quote->enquiry?->update(['status' => 'won']);

            return $order;
        });

        $this->activity->log('accepted', __('Quote :ref accepted, job :order created', [
            'ref' => $quote->reference,
            'order' => $order->reference,
        ]), $quote);

        return redirect()->route('orders.show', $order)
            ->with('status', __('Accepted. Job :ref is on the books.', ['ref' => $order->reference]));
    }

    public function decline(Request $request, Quote $quote): RedirectResponse
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $quote->update([
            'status' => 'declined',
            'decided_at' => now(),
            'notes' => $data['notes'] ?? $quote->notes,
        ]);

        $quote->enquiry?->update(['status' => 'lost']);

        $this->activity->log('declined', __('Quote :ref declined', ['ref' => $quote->reference]), $quote);

        return back()->with('status', __('Marked as declined.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'event_type' => ['nullable', 'string', 'max:64'],
            'event_date' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'guests' => ['required', 'integer', 'min:1', 'max:5000'],
            'service_style' => ['required', 'in:delivery,collection,delivered_and_served,full_buffet,not_set'],
            'discount_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'valid_until' => ['nullable', 'date'],
            'terms' => ['nullable', 'string', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $data['discount_percent'] = (int) ($data['discount_percent'] ?? 0);

        return $data;
    }
}

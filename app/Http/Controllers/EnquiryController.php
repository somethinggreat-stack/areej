<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnquiryRequest;
use App\Models\Enquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    public function create(): View
    {
        return view('site.contact');
    }

    /**
     * Stores the enquiry first, then notifies. If the notification fails the
     * lead is still safely recorded — losing it silently is the exact problem
     * this replaces.
     */
    public function store(StoreEnquiryRequest $request): RedirectResponse
    {
        $key = 'enquiry:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()
                ->withInput()
                ->withErrors(['name' => __('Too many enquiries from this device. Please call us instead.')]);
        }

        RateLimiter::hit($key, 3600);

        $enquiry = Enquiry::create([
            ...$request->safe()->except(['company_website', 'privacy_consent']),
            'ip_address' => $request->ip(),
            'privacy_accepted_at' => now(),
            'source' => 'website',
        ]);

        Log::info('Website enquiry received', [
            'reference' => $enquiry->reference,
            'event_date' => $enquiry->event_date?->toDateString(),
            'guests' => $enquiry->guests,
        ]);

        return redirect()
            ->route('site.contact')
            ->with('enquiry_reference', $enquiry->reference);
    }
}

<?php

return [
    /**
     * Places API (New) key for pulling live Google reviews.
     * Leave empty and the site falls back to a checked-in snapshot of real
     * reviews rather than showing nothing.
     */
    'places_key' => env('GOOGLE_PLACES_API_KEY'),
];

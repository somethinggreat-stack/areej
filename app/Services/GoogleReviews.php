<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Live Google reviews from Midland Catering's own Business Profile, fetched by
 * Place ID and cached for twelve hours so the site refreshes itself without
 * anyone touching the code.
 *
 * SETUP — add to .env:
 *   GOOGLE_PLACES_API_KEY=<key with "Places API (New)" enabled>
 *
 * LIMIT — the Places API returns at most 5 reviews per place. That is Google's
 * cap. The rating and total shown are the real figures across all reviews.
 */
class GoogleReviews
{
    private const CACHE_KEY = 'google-reviews';

    private const CACHE_TTL = 43200;

    /**
     * Genuine reviews captured from the public profile, used when no API key is
     * configured. These are real customers, not samples.
     *
     * @var list<array{author: string, rating: int, text: string, when: string}>
     */
    private const SNAPSHOT = [
        [
            'author' => 'T Ali',
            'rating' => 5,
            'when' => '7 months ago',
            'text' => "The food from Midland Catering was perfect for my daughter's wedding! We had roasts, masala fish, lamb chops, meat pilau rice, roti, meat curry, chicken palak, chaats. All had perfect amount of spice. We also had gajrela, gulab jamun and kheer.",
        ],
        [
            'author' => 'Adnan Suleman',
            'rating' => 5,
            'when' => 'a month ago',
            'text' => "We couldn't be happier with the service from Midland Catering! The food was absolutely amazing, and all of our guests commented on how delicious everything was. The team were so friendly, reliable, and professional throughout the whole process.",
        ],
        [
            'author' => 'Maheen Ahmed',
            'rating' => 5,
            'when' => 'a month ago',
            'text' => 'I ordered a chicken biryani for 20 people and picked it up at 9am before work. It was freshly cooked that morning and was delicious. Good value for money and actually fed just over 30 people.',
        ],
    ];

    /**
     * @return array{rating: float, total: int, reviews: list<array{author: string, rating: int, text: string, when: string}>, live: bool}
     */
    public function get(): array
    {
        $key = config('google.places_key');

        if (! $key) {
            return $this->snapshot();
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () use ($key): array {
            return $this->fetch($key) ?? $this->snapshot();
        });
    }

    /**
     * @return array{rating: float, total: int, reviews: list<array{author: string, rating: int, text: string, when: string}>, live: bool}|null
     */
    private function fetch(string $key): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-Goog-Api-Key' => $key,
                'X-Goog-FieldMask' => 'rating,userRatingCount,reviews',
            ])->timeout(8)->get(
                'https://places.googleapis.com/v1/places/'.config('midland.place_id'),
                ['languageCode' => 'en', 'regionCode' => 'GB']
            );

            if ($response->failed()) {
                Log::warning('Google Places API failed', ['status' => $response->status()]);

                return null;
            }

            $reviews = collect($response->json('reviews') ?? [])
                ->map(fn (array $r): array => [
                    'author' => trim($r['authorAttribution']['displayName'] ?? 'Google reviewer'),
                    'rating' => (int) ($r['rating'] ?? 0),
                    'text' => trim($r['originalText']['text'] ?? $r['text']['text'] ?? ''),
                    'when' => $r['relativePublishTimeDescription'] ?? '',
                ])
                // Short or poor reviews are not marketing copy; the link to the
                // full profile is right there for anyone who wants everything.
                ->filter(fn (array $r): bool => mb_strlen($r['text']) > 60 && $r['rating'] >= 4)
                ->sortByDesc(fn (array $r): int => $r['rating'] * 1000 + mb_strlen($r['text']))
                ->values()
                ->all();

            if ($reviews === []) {
                return null;
            }

            return [
                'rating' => (float) ($response->json('rating') ?? config('midland.google.rating')),
                'total' => (int) ($response->json('userRatingCount') ?? config('midland.google.review_count')),
                'reviews' => $reviews,
                'live' => true,
            ];
        } catch (\Throwable $e) {
            Log::warning('Google Places API error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * @return array{rating: float, total: int, reviews: list<array{author: string, rating: int, text: string, when: string}>, live: bool}
     */
    private function snapshot(): array
    {
        return [
            'rating' => (float) config('midland.google.rating'),
            'total' => (int) config('midland.google.review_count'),
            'reviews' => self::SNAPSHOT,
            'live' => false,
        ];
    }
}

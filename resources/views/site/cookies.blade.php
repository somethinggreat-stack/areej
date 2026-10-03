@extends('site.layout')

@php
    $mc = config('midland');
@endphp

@section('title', 'Cookie Policy')
@section('description', 'The cookies the Midland Catering website uses, what each one does, and how to change your choice.')

@section('content')
    @include('site.partials.legal-head', ['eyebrow' => 'Cookies', 'heading' => 'Cookie policy', 'updated' => '4 October 2026'])

    <section class="bg-ink pb-24 sm:pb-32">
        <div class="container-x">
            <div class="legal max-w-3xl">
            <p>
                Cookies are small files a website stores on your device. We keep them to a minimum: there are no analytics,
                advertising or tracking cookies on this website.
            </p>

            <h2>Essential cookies</h2>
            <p>These make the website work and keep the enquiry form secure. The law allows them without asking, and they cannot be switched off.</p>
            <div class="legal-table">
                <table>
                    <thead>
                        <tr><th>Name</th><th>What it does</th><th>How long</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>{{ config('session.cookie') }}</td><td>Keeps your visit together, so the enquiry form works and staff stay signed in.</td><td>{{ config('session.lifetime') }} minutes</td></tr>
                        <tr><td>XSRF-TOKEN</td><td>Protects forms from being sent by another website.</td><td>{{ config('session.lifetime') }} minutes</td></tr>
                        <tr><td>mc_cookie_consent</td><td>Remembers whether you accepted or rejected optional cookies, so we do not ask on every page.</td><td>1 year</td></tr>
                    </tbody>
                </table>
            </div>
            <p>
                We also store a small note in your browser (not a cookie, and never sent to us) so the opening animation only plays once
                per visit. It is cleared when you close the tab.
            </p>

            <h2>Optional: Google Maps</h2>
            <p>
                The map showing our kitchen comes from Google. Google sets its own cookies when it loads, so the map stays hidden until
                you accept optional cookies or press “Show map”. Google’s use of them is covered by
                <a href="https://policies.google.com/technologies/cookies" target="_blank" rel="noopener noreferrer">Google’s cookie policy</a>.
                The “Get directions” link opens Google Maps in a new tab instead, if you prefer.
            </p>

            <h2>Changing your choice</h2>
            <p>
                <button type="button" data-cookie-settings class="link-underline font-semibold text-gold">Open cookie settings</button>
                to accept or reject optional cookies at any time. A “Cookie settings” link is also at the bottom of every page.
                You can delete cookies in your browser settings too.
            </p>

            <h2>More information</h2>
            <p>
                How we use your personal information is explained in our <a href="{{ route('site.privacy') }}">privacy notice</a>.
                Questions: <a href="{{ $mc['email_href'] }}">{{ $mc['email'] }}</a> or <a href="{{ $mc['phone_href'] }}">{{ $mc['phone'] }}</a>.
            </p>
            </div>
        </div>
    </section>
@endsection

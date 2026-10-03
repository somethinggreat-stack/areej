@extends('site.layout')

@php
    $mc = config('midland');
    $address = $mc['address']['line1'].', '.$mc['address']['line2'].', '.$mc['address']['city'].' '.$mc['address']['postcode'];
@endphp

@section('title', 'Privacy Notice')
@section('description', 'How Midland Catering Ltd collects, uses and protects your personal information under UK GDPR.')

@section('content')
    @include('site.partials.legal-head', ['eyebrow' => 'Your information', 'heading' => 'Privacy notice', 'updated' => '4 October 2026'])

    <section class="bg-ink pb-24 sm:pb-32">
        <div class="container-x">
            <div class="legal max-w-3xl">
            <h2>Who we are</h2>
            <p>
                {{ $mc['legal_name'] }} (company number {{ $mc['company_number'] }}), {{ $address }}, is responsible for the personal
                information described here. We are the “data controller” under the UK General Data Protection Regulation (UK GDPR)
                and the Data Protection Act 2018.
            </p>
            <p>
                Questions or requests about your information: email <a href="{{ $mc['email_href'] }}">{{ $mc['email'] }}</a>,
                call <a href="{{ $mc['phone_href'] }}">{{ $mc['phone'] }}</a>, or write to us at the address above.
            </p>

            <h2>What we collect</h2>
            <p>When you send an enquiry through this website we collect what you type into the form:</p>
            <ul>
                <li>your name, phone number and email address;</li>
                <li>your event details: type of event, date, number of guests, venue, how you would like it served and any extras;</li>
                <li>dietary requirements and anything else you choose to tell us;</li>
                <li>the date and time you agreed to this notice, and your device’s IP address, which we use to stop spam.</li>
            </ul>
            <p>
                If you book with us we also keep the details of your order, what you paid and when, and the invoices we send you.
                We do not collect payment card details through this website.
            </p>
            <p>
                Dietary requirements can include allergy or health information. We only use it to prepare food that is safe for your
                guests. Please give numbers (for example “4 nut allergies”) rather than naming individuals.
            </p>

            <h2>Why we use it, and our lawful basis</h2>
            <ul>
                <li><strong>To reply to your enquiry and quote for your event:</strong> steps you have asked us to take before entering a contract.</li>
                <li><strong>To cater your event:</strong> performing our contract with you.</li>
                <li><strong>To keep order, payment and invoice records:</strong> our legal obligation to keep business and tax records.</li>
                <li><strong>To protect the website from spam and misuse:</strong> our legitimate interests.</li>
                <li><strong>Allergy and health information:</strong> your explicit consent, given when you write it in. You can withdraw it at any time.</li>
            </ul>
            <p>We do not sell your information, use it for advertising, or send you marketing without asking first.</p>

            <h2>Who we share it with</h2>
            <p>Only where needed to run the business:</p>
            <ul>
                <li>the company that hosts this website and its database;</li>
                <li>our accountant, and HMRC or other authorities where the law requires it;</li>
                <li>Google, only if you choose to load the map on our website (see our <a href="{{ route('site.cookies') }}">cookie policy</a>).</li>
            </ul>
            <p>If any information is processed outside the UK, we make sure suitable safeguards are in place, as UK GDPR requires.</p>

            <h2>How long we keep it</h2>
            <ul>
                <li><strong>Enquiries that do not become a booking:</strong> up to 2 years from the enquiry, then deleted.</li>
                <li><strong>Orders, payments and invoices:</strong> 6 years from the end of the tax year they relate to, as HMRC requires.</li>
            </ul>

            <h2>Keeping it safe</h2>
            <p>
                The website uses an encrypted connection (HTTPS). Only our managing team can sign in to see enquiries and orders, each with
                their own password and optional two-step sign-in. Prices and payments are restricted to management.
            </p>

            <h2>Your rights</h2>
            <p>You have the right to:</p>
            <ul>
                <li>ask for a copy of the information we hold about you;</li>
                <li>ask us to correct anything that is wrong;</li>
                <li>ask us to delete your information, or to stop or limit using it;</li>
                <li>object to how we use it, or ask for it in a format you can take elsewhere;</li>
                <li>withdraw consent you have given, at any time.</li>
            </ul>
            <p>
                Contact us using the details above. We will reply within one month. Some records, such as invoices, we must keep by law
                even if you ask us to delete them; we will tell you if that applies.
            </p>

            <h2>Complaints</h2>
            <p>
                If you are unhappy with how we have handled your information, please tell us first so we can put it right. You can also
                complain to the Information Commissioner’s Office: <a href="https://ico.org.uk/make-a-complaint/" target="_blank" rel="noopener noreferrer">ico.org.uk</a>,
                0303 123 1113.
            </p>

            <h2>Changes to this notice</h2>
            <p>If we change how we use your information we will update this page and the date at the top.</p>
            </div>
        </div>
    </section>
@endsection

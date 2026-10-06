@php $mc = config('midland'); @endphp
<footer class="foot">
    <div class="foot__row">
        <p class="foot__call"><b>For inquiries, call</b>{{ $mc['phone'] }}</p>
        <div class="foot__people">
            @foreach ($mc['contacts'] as $contact)
                <span><strong>{{ $contact['name'] }}</strong>{{ $contact['phone'] }}</span>
            @endforeach
        </div>
    </div>
    <div class="foot__line">
        <span>{{ $mc['address']['line1'] }}, {{ $mc['address']['line2'] }}, {{ $mc['address']['city'] }} {{ $mc['address']['postcode'] }} · {{ $mc['email'] }} · WhatsApp {{ $mc['whatsapp'] }}</span>
        @isset($page)
            <span class="foot__page">{{ $page }}</span>
        @endisset
    </div>
</footer>

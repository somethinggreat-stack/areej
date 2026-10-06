@php $mc = config('midland'); @endphp
<header class="band">
    <div class="band__brand">
        <img src="{{ asset('img/midland-logo.png') }}" alt="">
        <p class="band__name">{{ $mc['name'] }}<small>{{ $mc['city'] }}</small></p>
    </div>
    <div class="band__right">
        <p class="band__super">{{ $mc['supervision'] }}</p>
        <p class="tag">{{ $mc['tagline'] }}</p>
        <p class="halal">{{ $mc['speciality'] }} · Halal</p>
    </div>
</header>

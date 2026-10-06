{{-- A list of dishes from config/menu.php. Pass $dishes and, optionally, $columns. --}}
<ul class="dishes" style="--cols: {{ $columns ?? 2 }}">
    @foreach ($dishes as $dish)
        <li class="dish">
            <p class="dish__name">
                {{ $dish['name'] }}
                @if ($dish['signature'])
                    <span class="dish__sig" title="Signature dish">◆</span>
                @endif
                @foreach ($dish['tags'] as $tag)
                    <span class="dish__tag">{{ $tag }}</span>
                @endforeach
            </p>
            <p class="dish__desc">{{ $dish['desc'] }}</p>
        </li>
    @endforeach
</ul>

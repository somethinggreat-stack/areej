@props(['head' => [], 'align' => []])

<div class="card overflow-hidden">
    <div class="table-scroll">
        <table {{ $attributes->merge(['class' => 'w-full text-sm']) }}>
            @if (count($head))
                <thead class="border-b border-line bg-surface-2 text-start">
                    <tr>
                        @foreach ($head as $i => $label)
                            <th scope="col" class="label-sm px-4 py-3 whitespace-nowrap {{ ($align[$i] ?? 'start') === 'end' ? 'text-end' : 'text-start' }}">
                                {{ $label }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody class="divide-y divide-line">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>

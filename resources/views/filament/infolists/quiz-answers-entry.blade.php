@php $breakdown = \App\Support\QuizAnswerBreakdown::build($getRecord()->quiz_answers); @endphp

<div class="space-y-4">
    @forelse ($breakdown as $item)
        <div class="border-b border-gray-100 pb-3 last:border-0 last:pb-0 dark:border-white/5">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $item['question'] }}</p>
            <div class="mt-1 flex flex-wrap gap-3">
                @foreach ($item['options'] as $option)
                    <div class="flex items-center gap-2">
                        <img
                            src="{{ $option['image'] }}"
                            alt="{{ $option['title'] }}"
                            style="width: 40px; height: 40px; object-fit: cover; flex-shrink: 0;"
                            class="rounded-lg bg-gray-100 dark:bg-gray-800"
                            onerror="this.style.visibility='hidden'"
                        />
                        <div>
                            <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $option['title'] }}</p>
                            @if ($option['productName'])
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $option['productName'] }}</p>
                            @endif
                            @if ($option['internalNote'])
                                <p class="text-xs italic text-gray-500 dark:text-gray-400">Notitie: {{ $option['internalNote'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">Geen antwoorden gevonden.</p>
    @endforelse
</div>

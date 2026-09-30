@php $breakdown = \App\Support\QuizAnswerBreakdown::build($getRecord()->quiz_answers); @endphp

<div class="space-y-5">
    @forelse ($breakdown as $item)
        <div class="border-b border-gray-100 pb-4 last:border-0 last:pb-0 dark:border-white/5">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $item['question'] }}</p>

            @if ($item['skipped'])
                <p class="mt-1.5 text-sm italic text-gray-400 dark:text-gray-500">Overgeslagen — geen keuze gemaakt.</p>
            @else
                <div class="mt-2 flex flex-wrap gap-4">
                    @foreach ($item['options'] as $option)
                        <div class="flex items-center gap-3">
                            <div class="relative shrink-0" style="width: 64px; height: 64px;">
                                <img
                                    src="{{ $option['image'] }}"
                                    alt="{{ $option['title'] }}"
                                    style="width: 64px; height: 64px; object-fit: cover;"
                                    class="rounded-lg bg-gray-100 dark:bg-gray-800"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                />
                                <div
                                    class="hidden items-center justify-center rounded-lg bg-gray-100 text-center text-[10px] leading-tight text-gray-400 dark:bg-gray-800"
                                    style="width: 64px; height: 64px;"
                                >Geen afbeelding</div>
                            </div>
                            <div class="min-w-0">
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
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">Geen antwoorden gevonden.</p>
    @endforelse
</div>

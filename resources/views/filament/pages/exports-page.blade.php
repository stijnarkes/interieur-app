<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Leads exporteren</x-slot>
        <x-slot name="description">
            Exporteer alle inzendingen met een e-mailadres als CSV-bestand.
        </x-slot>

        <div class="space-y-4">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Het CSV-bestand bevat de volgende kolommen:
                </p>
                <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-gray-600 dark:text-gray-300">
                    <li>Datum</li>
                    <li>Naam</li>
                    <li>E-mail</li>
                    <li>Stijl</li>
                    <li>Marketing opt-in</li>
                    <li>Kamerafoto (Ja/Nee)</li>
                    <li>Resultaat gegenereerd (Ja/Nee)</li>
                </ul>
            </div>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Gebruik de knop rechtsboven om de download te starten.
            </p>
        </div>
    </x-filament::section>
</x-filament-panels::page>

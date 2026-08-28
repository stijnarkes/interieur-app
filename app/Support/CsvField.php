<?php

namespace App\Support;

class CsvField
{
    /**
     * Zet een waarde om naar een veilig, aanhalingsteken-omsloten CSV-veld. Voorkomt CSV-/
     * formule-injectie: als de waarde begint met een teken dat Excel/Sheets/LibreOffice als
     * formule interpreteert (=, +, -, @, tab, CR), wordt er een voorloop-apostrof voor gezet
     * zodat het als platte tekst wordt gelezen in plaats van uitgevoerd.
     */
    public static function escape(?string $value): string
    {
        $value = (string) $value;

        if (preg_match('/^[=+\-@\t\r]/', $value) === 1) {
            $value = "'".$value;
        }

        return '"'.str_replace('"', '""', $value).'"';
    }
}

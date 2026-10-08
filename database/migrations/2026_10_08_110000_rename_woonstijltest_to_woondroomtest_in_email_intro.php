<?php

use App\Models\SiteContent;
use Illuminate\Database\Migrations\Migration;

/**
 * De test zelf heet voortaan "Woondroomtest" i.p.v. "(interieur)woonstijltest" (zie de commit die
 * dit overal in de zichtbare tekst/branding al doorvoerde). email_intro is admin-bewerkbaar via de
 * Teksten-pagina, dus net als bij eerdere tekstrondes alleen bijwerken als de huidige waarde nog
 * exact de oude, oorspronkelijk geseede tekst is — een admin die dit veld intussen zelf al
 * aangepast heeft, wordt hier dus nooit overschreven.
 */
return new class extends Migration
{
    public function up(): void
    {
        SiteContent::query()
            ->where('email_intro', 'Bedankt voor het doen van de interieurstijltest van Boer Staphorst. Jouw woonstijl:')
            ->update(['email_intro' => 'Bedankt voor het doen van de Woondroomtest van Boer Staphorst. Jouw woonstijl:']);
    }

    public function down(): void
    {
        // Bewust geen terugdraai-logica: dit is een tekstuele naamswijziging, geen structuurwijziging.
    }
};

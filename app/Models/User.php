<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'can_manage_quiz',
        'can_view_results',
        'leads_viewed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'can_manage_quiz' => 'boolean',
            'can_view_results' => 'boolean',
            'leads_viewed_at' => 'datetime',
        ];
    }

    /** Een volledig beheerder heeft altijd overal toegang toe, ongeacht de losse rechten-vlaggen. */
    public function canManageQuiz(): bool
    {
        return $this->is_admin || $this->can_manage_quiz;
    }

    public function canViewResults(): bool
    {
        return $this->is_admin || $this->can_view_results;
    }

    /**
     * Filament berekent dit navigatiebadge-getal op élke admin-pagina (niet alleen op de
     * Leads-pagina zelf), dus dit draait bij elke paginanavigatie mee. Gecached via de
     * file-store (nooit de standaard database-store — dat zou de query alleen verplaatsen naar
     * een andere tabel, niet de round-trip naar de trage/soms-slapende database wegnemen) zodat
     * de meeste paginabezoeken dit getal lokaal uit een bestand lezen in plaats van de database
     * te raken. 30 seconden veroudering is voor een badge-teller ruim acceptabel.
     */
    public function newLeadsCount(): int
    {
        return Cache::store('file')->remember(
            "new-leads-count:{$this->id}",
            30,
            fn () => Submission::whereNotNull('email')
                ->when($this->leads_viewed_at, fn ($query) => $query->where('created_at', '>', $this->leads_viewed_at))
                ->count(),
        );
    }

    public function markLeadsAsViewed(): void
    {
        $this->forceFill(['leads_viewed_at' => now()])->save();
        Cache::store('file')->forget("new-leads-count:{$this->id}");
    }
}

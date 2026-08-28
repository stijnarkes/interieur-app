<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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

    public function newLeadsCount(): int
    {
        return Submission::whereNotNull('email')
            ->when($this->leads_viewed_at, fn ($query) => $query->where('created_at', '>', $this->leads_viewed_at))
            ->count();
    }

    public function markLeadsAsViewed(): void
    {
        $this->forceFill(['leads_viewed_at' => now()])->save();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eén "partnerkoppeling" per uitnodiging (zie het implementatieplan "Partnerfunctie: Ontdek
 * jullie gezamenlijke woonstijl"). `invite_token_hash` is de enige manier om deze rij op te
 * zoeken vanaf een binnenkomende link — de plaintext staat nergens in de database, behalve
 * versleuteld in `invite_token_encrypted` (voor het idempotent opnieuw tonen van dezelfde
 * uitnodigingslink aan de initiator, zie App\Support\PartnerToken).
 */
class PartnerLink extends Model
{
    public const STATUS_WAITING = 'waiting';

    public const STATUS_PARTNER_STARTED = 'partner_started';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'initiator_quiz_result_id',
        'initiator_snapshot',
        'partner_quiz_result_id',
        'partner_snapshot',
        'status',
        'invite_token_hash',
        'invite_token_encrypted',
        'invite_expires_at',
        'initiator_name',
        'partner_name',
        'share_confirmed_at',
        'share_confirmation_text_version',
        'completed_at',
        'revoked_at',
    ];

    protected $casts = [
        'initiator_snapshot' => 'array',
        'partner_snapshot' => 'array',
        'invite_expires_at' => 'datetime',
        'share_confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function initiatorQuizResult()
    {
        return $this->belongsTo(QuizResult::class, 'initiator_quiz_result_id');
    }

    public function partnerQuizResult()
    {
        return $this->belongsTo(QuizResult::class, 'partner_quiz_result_id');
    }

    public function participants()
    {
        return $this->hasMany(PartnerParticipant::class);
    }

    public function comparisons()
    {
        return $this->hasMany(PartnerComparison::class);
    }

    /** De (enige) actieve vergelijking — als hasOne/latestOfMany zodat admin-weergaves (zie
     *  PartnerLinkResource) 'm net als een gewone relatie via dot-notatie kunnen tonen. */
    public function comparison()
    {
        return $this->hasOne(PartnerComparison::class, 'partner_link_id')->latestOfMany();
    }

    public function isExpired(): bool
    {
        return $this->invite_expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->status === self::STATUS_REVOKED;
    }

    /** Een uitnodiging mag geclaimd worden zolang die niet verlopen, ingetrokken, of al geclaimd is. */
    public function isClaimable(): bool
    {
        if ($this->isRevoked() || $this->isExpired()) {
            return false;
        }

        return $this->status === self::STATUS_WAITING;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eén deelnemer (initiator of partner) aan een PartnerLink. De db-constraint
 * unique(['partner_link_id','role']) is het daadwerkelijke atomaire-claim-mechanisme — zie
 * App\Http\Controllers\PartnerLinkController::claim().
 */
class PartnerParticipant extends Model
{
    public const ROLE_INITIATOR = 'initiator';

    public const ROLE_PARTNER = 'partner';

    protected $fillable = [
        'partner_link_id',
        'role',
        'quiz_result_id',
        'access_token_hash',
        'email',
        'mail_requested_at',
        'mail_status',
        'mail_error',
    ];

    protected $casts = [
        'mail_requested_at' => 'datetime',
    ];

    public function partnerLink()
    {
        return $this->belongsTo(PartnerLink::class);
    }

    public function quizResult()
    {
        return $this->belongsTo(QuizResult::class);
    }
}

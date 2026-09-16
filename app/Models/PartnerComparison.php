<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Eén (actieve) vergelijking per PartnerLink — zie App\Services\PartnerComparisonService. */
class PartnerComparison extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'partner_link_id',
        'algorithm_version',
        'content_version',
        'facts',
        'suggestions',
        'status',
        'error_message',
        'pdf_path',
    ];

    protected $casts = [
        'facts' => 'array',
        'suggestions' => 'array',
    ];

    public function partnerLink()
    {
        return $this->belongsTo(PartnerLink::class);
    }
}

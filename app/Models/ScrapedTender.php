<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScrapedTender extends Model
{
    protected $table = 'scraped_tenders';

    protected $fillable = [
        'source_url',
        'source_list_url',
        'title',
        'authority',
        'location',
        'description',
        'type',
        'category',
        'platform',
        'opening_date',
        'closing_date',
        'closing_label',
        'tender_amount',
        'emd',
        'tender_id',
        'tender_no',
        'tender_authority',
        'purchaser_address',
        'website',
        'tender_url',
        'raw_payload',
    ];

    public function documents()
    {
        return $this->hasMany(ScrapedTenderDocument::class, 'scraped_tender_id');
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScrapedTenderDocument extends Model
{
    protected $table = 'scraped_tender_documents';

    protected $fillable = [
        'scraped_tender_id',
        'doc_label',
        'file_name',
        'file_size',
        'doc_url',
    ];

    public function tender()
    {
        return $this->belongsTo(ScrapedTender::class, 'scraped_tender_id');
    }
}


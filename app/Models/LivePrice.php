<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LivePrice extends Model
{
    protected $table = 'live_prices';

    protected $fillable = [
        'location',
        'item',
        'category',
        'city',
        'buy_price',
        'sell_price',
        'lme_price',
        'mcx_price',
        'injection_moulding',
        'battery_price',
        'pe_63',
        'drum_scrap',
        'blow',
        'pe_100',
        'crate',
        'black_cable',
        'white_pipe',
        'grey_pvc',
        'updated_at',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to filter by location
     */
    public function scopeByLocation($query, $location)
    {
        return $query->where('location', $location);
    }

    /**
     * Scope to filter by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get formatted price display
     */
    public function getFormattedBuyPriceAttribute()
    {
        return $this->buy_price ? '₹' . number_format($this->buy_price, 2) : '-';
    }

    /**
     * Get formatted sell price display
     */
    public function getFormattedSellPriceAttribute()
    {
        return $this->sell_price ? '₹' . number_format($this->sell_price, 2) : '-';
    }
}

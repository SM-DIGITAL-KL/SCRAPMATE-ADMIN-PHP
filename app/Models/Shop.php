<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Models\ProductCategory;

class Shop extends Model
{
    protected $table = 'shops';

    static public function criteriaCount($criteria)
    {
        // Since we're using Node.js API, return 0 to avoid database queries
        // The actual counts should come from the API response in the controller
        // This method is kept for backward compatibility but doesn't query the database
        try {
            // Try to get from cache if available (set by controller)
            $cacheKey = "shop_criteria_count_{$criteria}";
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                return \Illuminate\Support\Facades\Cache::get($cacheKey);
            }
        } catch (\Exception $e) {
            // Ignore cache errors
        }
        
        // Return 0 as fallback - actual counts should come from Node.js API
        return 0;
    }

}

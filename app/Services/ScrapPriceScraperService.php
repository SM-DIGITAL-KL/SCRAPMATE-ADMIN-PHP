<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ScrapPriceScraperService
{
    private $urls = [
        'https://scrapc.com/news/delhi-scrap-price-today/',
        'https://scrapc.com/news/mumbais-scrap-todays-metal-prices-update/',
        'https://scrapc.com/news/gobindgarh-mandi-scrap-rate-today/',
        'https://scrapc.com/news/iron-scrap-rate-today/',
        'https://scrapc.com/news/today-scrap-rate-in-kolkata/',
        'https://scrapc.com/news/todays-scrap-rates-in-ahmedabad/',
        'https://scrapc.com/news/hms-scrap-rate-today/',
        'https://scrapc.com/news/today-pig-iron-rates/',
        'https://scrapc.com/news/daily-scrap-copper-prices/',
        'https://scrapc.com/news/jamnagar-scrap-price-update/',
        'https://scrapc.com/news/ms-ingot-price-today/',
        'https://scrapc.com/news/sponge-iron-price/',
        'https://scrapc.com/news/lme-warehouse-rates/',
        'https://scrapc.com/news/south-india-daily-scrap-prices/',
        'https://scrapc.com/news/hot-rolled-coil/',
        'https://scrapc.com/news/cold-rolled-coil/',
        'http://scrapc.com/news/metal-prices-today-daily-rates-for-copper-aluminum-nickel-zinc-lead-and-tin/',
        'https://scrapc.com/news/pet-bottle-price/',
        'https://scrapc.com/news/pp-plastic-scrap-prices-in-india/',
        'https://scrapc.com/news/hdpe-scrap-price/',
        'https://scrapc.com/news/pvc-scrap-price/',
        'https://scrapc.com/news/hdpe-granules-price/',
        'https://scrapc.com/news/polypropylene-copolymer-price/',
        'https://scrapc.com/news/ldpe-price-india/',
        'https://scrapc.com/news/raddi-paper-scrap-rate/'
    ];

    /**
     * Scrape all URLs and return results
     */
    public function scrapeAll()
    {
        $allResults = [];
        $successCount = 0;
        $failCount = 0;

        Log::info('🚀 Starting scrap price scraping process');

        foreach ($this->urls as $url) {
            try {
                $results = $this->scrapeUrl($url);
                if (!empty($results)) {
                    $allResults = array_merge($allResults, $results);
                    $successCount++;
                }
            } catch (\Exception $e) {
                Log::error("❌ Failed to scrape {$url}: " . $e->getMessage());
                $failCount++;
            }
        }

        Log::info("✅ Scraping completed: {$successCount} success, {$failCount} failed");

        return [
            'success' => true,
            'results' => $allResults,
            'stats' => [
                'total_urls' => count($this->urls),
                'success' => $successCount,
                'failed' => $failCount,
                'total_items' => count($allResults)
            ]
        ];
    }

    /**
     * Scrape a single URL
     */
    private function scrapeUrl($url)
    {
        Log::info("📥 Fetching data from {$url}");

        $response = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])
            ->get($url);

        if (!$response->successful()) {
            throw new \Exception("HTTP request failed with status " . $response->status());
        }

        $html = $response->body();
        $allResults = [];

        // Use DOMDocument to parse HTML
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $tables = $xpath->query('//table');

        foreach ($tables as $table) {
            $results = $this->parseTable($table, $xpath, $url);
            if (!empty($results)) {
                $allResults = array_merge($allResults, $results);
            }
        }

        return $allResults;
    }

    /**
     * Parse a single table
     */
    private function parseTable($table, $xpath, $url)
    {
        $results = [];
        $rows = $xpath->query('.//tr', $table);

        if ($rows->length == 0) {
            return $results;
        }

        // Get header row to determine location
        $headerRow = $this->getNodeText($rows->item(0));
        $location = $this->determineLocation($headerRow, $url);

        if ($location === 'Unknown') {
            return $results;
        }

        $currentCategory = '';

        foreach ($rows as $i => $row) {
            if ($i === 0) continue; // Skip header

            $columns = $xpath->query('.//td | .//th', $row);
            if ($columns->length < 2) continue;

            $name = trim($this->getNodeText($columns->item(0)));
            $val1 = $columns->length >= 2 ? trim($this->getNodeText($columns->item(1))) : '';
            $val2 = $columns->length >= 3 ? trim($this->getNodeText($columns->item(2))) : '';

            // Header/Category detection
            if ($name && !$val1 && !$val2) {
                $currentCategory = $name;
                continue;
            }

            if (!$name || $this->isHeaderLabel($name)) {
                continue;
            }

            // Parse based on location type
            $priceData = $this->parsePriceData($location, $name, $val1, $val2, $columns, $xpath, $currentCategory);
            if ($priceData) {
                $priceData['location'] = $location;
                $results[] = $priceData;
            }
        }

        Log::info("📊 Parsed {$location}: " . count($results) . " items");

        return $results;
    }

    /**
     * Determine location from header text
     */
    private function determineLocation($headerRow, $url)
    {
        $header = strtoupper($headerRow);

        if (strpos($header, 'DELHI') !== false) return 'Delhi';
        if (strpos($header, 'MUMBAI') !== false) return 'Mumbai';
        if (strpos($header, 'PUNE') !== false) return 'Pune';
        if (strpos($header, 'GOBINDGARH') !== false) return 'Mandi Gobindgarh';
        if (strpos($header, 'IRON SCRAP') !== false) return 'All India Iron Scrap';
        if (strpos($header, 'KOLKATA') !== false) return 'Kolkata';
        if (strpos($header, 'AHMEDABAD') !== false) return 'Ahmedabad';
        if (strpos($header, 'TURKEY') !== false || strpos($header, 'HMS') !== false) return 'HMS Global/Regional';
        if (strpos($header, 'PIG IRON') !== false) return 'Pig Iron';
        if (strpos($header, 'COPPER SCRAP') !== false) return 'Copper Global/Regional';
        if (strpos($header, 'JAMNAGAR') !== false) return 'Jamnagar';
        if (strpos($header, 'INGOT') !== false) return 'MS Ingot';
        if (strpos($header, 'LME WAREHOUSE') !== false) return 'LME Warehouse Stock';
        if (strpos($header, 'SOUTH INDIAN') !== false) return 'South India';
        if (strpos($header, 'MILL') !== false) return 'South India (Mill)';
        
        if (strpos($header, 'CITY') !== false && strpos($header, 'STATE') !== false) {
            if (strpos($url, 'cold-rolled') !== false) return 'Cold Rolled Coil';
            if (strpos($url, 'pet-bottle') !== false) return 'PET Bottle';
            if (strpos($url, 'polypropylene-copolymer') !== false) return 'PPCP (Battery Plastic)';
            return 'Hot Rolled Coil';
        }
        
        if (strpos($header, 'LME ($/TON)') !== false && strpos($header, 'MCX') !== false) return 'Metal LME/MCX Rates';
        if (strpos($header, 'RAFFIA') !== false) return 'PP Plastic (Raffia)';
        if (strpos($header, 'JAMBO BAG') !== false || strpos($header, 'JUMBO BAG') !== false) return 'PP Plastic (Jumbo Bag)';
        if (strpos($header, 'PE 63') !== false) return 'HDPE Scrap';
        if (strpos($header, 'BLACK CABLE') !== false) return 'PVC Scrap';
        if (strpos($header, 'PLASTIC DANA') !== false) return 'HDPE Granules';
        if (strpos($header, 'PPCP') !== false && strpos($header, 'INJECTION') !== false) return 'PPCP Market Rates';
        if (strpos($header, 'LDPE PRICE') !== false && strpos($header, 'FILM') !== false) return 'LDPE Granules';
        if (strpos($header, 'DRIP PIPE') !== false) return 'LDPE (Drip/Film)';
        if (strpos($header, 'MILK POUCHES') !== false) return 'LDPE (Pouches/Bottle)';
        if (strpos($header, 'RADDI PAPER') !== false) return 'Raddi Paper';
        if (strpos($header, 'PAPER GATTA') !== false) return 'Paper Gatta';

        return 'Unknown';
    }

    /**
     * Parse price data based on location type
     */
    private function parsePriceData($location, $name, $val1, $val2, $columns, $xpath, $currentCategory)
    {
        $cityLocations = ['All India Iron Scrap', 'Hot Rolled Coil', 'Cold Rolled Coil', 'PET Bottle', 'PPCP (Battery Plastic)', 'Raddi Paper'];
        
        if (in_array($location, $cityLocations) || strpos($location, 'LDPE (') === 0) {
            return [
                'city' => $name,
                'item' => $name,
                'buy_price' => $val1,
                'sell_price' => $val2,
                'category' => 'General'
            ];
        }

        if ($location === 'Metal LME/MCX Rates') {
            return [
                'item' => $name,
                'lme_price' => $val1,
                'mcx_price' => $val2,
                'category' => 'Metal'
            ];
        }

        if ($location === 'PPCP Market Rates') {
            return [
                'item' => $name,
                'injection_moulding' => $val1,
                'battery_price' => $val2,
                'category' => 'PPCP'
            ];
        }

        if ($location === 'HDPE Scrap') {
            $val3 = $columns->length >= 4 ? trim($this->getNodeText($columns->item(3))) : '';
            $val4 = $columns->length >= 5 ? trim($this->getNodeText($columns->item(4))) : '';
            $val5 = $columns->length >= 6 ? trim($this->getNodeText($columns->item(5))) : '';
            
            return [
                'city' => $name,
                'item' => $name,
                'pe_63' => $val1,
                'drum_scrap' => $val2,
                'blow' => $val3,
                'pe_100' => $val4,
                'crate' => $val5,
                'category' => 'HDPE'
            ];
        }

        if ($location === 'PVC Scrap') {
            $val3 = $columns->length >= 4 ? trim($this->getNodeText($columns->item(3))) : '';
            
            return [
                'city' => $name,
                'item' => $name,
                'black_cable' => $val1,
                'white_pipe' => $val2,
                'grey_pvc' => $val3,
                'category' => 'PVC'
            ];
        }

        // Default format
        return [
            'item' => $name,
            'buy_price' => $val1,
            'sell_price' => $val2,
            'category' => $currentCategory ?: 'General'
        ];
    }

    /**
     * Check if text is a header label
     */
    private function isHeaderLabel($text)
    {
        $lowerText = strtolower($text);
        $headerKeywords = [
            'scrap price', 'rates', 'spot', 'all india', 'lme warehouse',
            'south indian', 'mill', 'city', 'lme ($', 'states',
            'plastic dana', 'ldpe price', 'raddi paper', 'paper gatta'
        ];

        foreach ($headerKeywords as $keyword) {
            if (strpos($lowerText, $keyword) !== false) {
                return true;
            }
        }

        return $lowerText === 'city' || $lowerText === 'states';
    }

    /**
     * Get text content from DOM node
     */
    private function getNodeText($node)
    {
        if (!$node) return '';
        return $node->textContent;
    }
}

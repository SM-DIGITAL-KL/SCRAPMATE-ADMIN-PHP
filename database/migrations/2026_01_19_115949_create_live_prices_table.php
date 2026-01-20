<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('live_prices', function (Blueprint $table) {
            $table->id();
            $table->string('location')->index(); // e.g., 'Delhi', 'Mumbai', etc.
            $table->string('item'); // Item name
            $table->string('category')->nullable()->index(); // Category like 'General', 'Iron Scrap', etc.
            $table->string('city')->nullable(); // Specific city if applicable
            
            // Standard price fields
            $table->string('buy_price')->nullable();
            $table->string('sell_price')->nullable();
            
            // Metal LME/MCX rates
            $table->string('lme_price')->nullable();
            $table->string('mcx_price')->nullable();
            
            // PPCP rates
            $table->string('injection_moulding')->nullable();
            $table->string('battery_price')->nullable();
            
            // HDPE Scrap fields
            $table->string('pe_63')->nullable();
            $table->string('drum_scrap')->nullable();
            $table->string('blow')->nullable();
            $table->string('pe_100')->nullable();
            $table->string('crate')->nullable();
            
            // PVC Scrap fields
            $table->string('black_cable')->nullable();
            $table->string('white_pipe')->nullable();
            $table->string('grey_pvc')->nullable();
            
            $table->timestamps();
            
            // Composite index for unique identification
            $table->unique(['location', 'item', 'category'], 'live_prices_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_prices');
    }
};

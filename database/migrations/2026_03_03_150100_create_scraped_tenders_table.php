<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scraped_tenders', function (Blueprint $table) {
            $table->id();
            $table->string('source_url')->unique();
            $table->string('source_list_url')->nullable();
            $table->string('title')->nullable();
            $table->string('authority')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->string('category')->nullable();
            $table->string('platform')->nullable();
            $table->string('opening_date')->nullable();
            $table->string('closing_date')->nullable();
            $table->string('closing_label')->nullable();
            $table->string('tender_amount')->nullable();
            $table->string('emd')->nullable();
            $table->string('tender_id')->nullable();
            $table->string('tender_no')->nullable();
            $table->string('tender_authority')->nullable();
            $table->string('purchaser_address')->nullable();
            $table->string('website')->nullable();
            $table->string('tender_url')->nullable();
            $table->longText('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scraped_tenders');
    }
};


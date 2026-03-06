<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scraped_tender_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scraped_tender_id');
            $table->string('doc_label')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_size')->nullable();
            $table->string('doc_url')->nullable();
            $table->timestamps();

            $table->index('scraped_tender_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scraped_tender_documents');
    }
};


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
        if (!Schema::hasTable('request_list')) {
            Schema::create('request_list', function (Blueprint $table) {
                $table->id();
                $table->string('title_job');
                $table->string('status');
                $table->string('by_request');
                $table->string('do_date');
                $table->longText('member');
                $table->longText('item_request');
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('request_list')) {
            Schema::dropIfExists('request_list');
        }
    }
};
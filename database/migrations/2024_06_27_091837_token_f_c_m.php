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
        if (!Schema::hasTable('fcm_token')) {
            Schema::create('fcm_token', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('id_user');
                $table->longText('token');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('fcm_token')) {
            Schema::dropIfExists('fcm_token');
        }
    }
};
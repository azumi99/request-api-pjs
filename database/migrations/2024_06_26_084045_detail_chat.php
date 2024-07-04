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
         if (!Schema::hasTable('detail_chat')) {
            Schema::create('detail_chat', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('id_chat');
                $table->bigInteger('id_user');
                $table->integer('_id');
                $table->longText('message');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('detail_chat')) {
            Schema::dropIfExists('detail_chat');
        }
    }
};
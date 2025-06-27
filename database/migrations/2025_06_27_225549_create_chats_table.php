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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // Para chats em grupo
            $table->enum('type', ['private', 'group'])->default('private');
            $table->text('description')->nullable(); // Para chats em grupo
            $table->unsignedBigInteger('created_by')->nullable(); // Quem criou o chat
            $table->string('created_by_type')->nullable(); // 'user' ou 'admin'
            $table->timestamps();
            
            // Índices
            $table->index(['type', 'created_by', 'created_by_type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};

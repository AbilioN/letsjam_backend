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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->text('content');
            $table->unsignedBigInteger('sender_id');
            $table->enum('sender_type', ['user', 'admin']);
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            // Chaves estrangeiras
            $table->foreign('chat_id')->references('id')->on('chats')->onDelete('cascade');
            
            // Índices para melhor performance
            $table->index(['chat_id', 'created_at']);
            $table->index(['sender_type', 'sender_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

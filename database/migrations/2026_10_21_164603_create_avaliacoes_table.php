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
        Schema::create('avaliacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nome', 120);
            $table->unsignedTinyInteger('nota');
            $table->text('mensagem');
            $table->boolean('publicacao_permitida')->default(false);
            $table->timestamp('aprovada_em')->nullable();
            $table->timestamps();

            $table->index(['publicacao_permitida', 'aprovada_em']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avaliacoes');
    }
};

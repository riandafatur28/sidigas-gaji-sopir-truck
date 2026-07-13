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
        Schema::create('gajis', function (Blueprint $table) {
            $table->id();
            $table->string('kode_sopir', 20);
            $table->foreign('kode_sopir')
                ->references('kode_sopir')
                ->on('sopirs')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreignId('periode_id')
                ->constrained('periodes')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->decimal('total_solar', 15, 2)->default(0)->comment('Total biaya solar');
            $table->decimal('total_upah', 15, 2)->default(0)->comment('Total upah sopir');
            $table->decimal('total_sewa_dt', 15, 2)->default(0)->comment('Total sewa dump truck');
            $table->decimal('grand_total', 15, 2)->default(0)->comment('Total keseluruhan');

            $table->timestamps();

            // Index untuk performa
            $table->index(['kode_sopir', 'periode_id']);
            $table->index('created_at');

            // Unique constraint untuk mencegah duplikasi
            $table->unique(['kode_sopir', 'periode_id'], 'unique_sopir_periode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gajis');
    }
};

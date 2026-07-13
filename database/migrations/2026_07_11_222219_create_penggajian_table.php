<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penggajian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_id')->constrained('periodes')->onDelete('cascade');
            $table->foreignId('tujuan_id')->constrained('tujuans')->onDelete('cascade');
            $table->date('tanggal');
            $table->decimal('uang_solar', 15, 2)->default(0);
            $table->decimal('upah_sopir', 15, 2)->default(0);
            $table->decimal('dt', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penggajian');
    }
};

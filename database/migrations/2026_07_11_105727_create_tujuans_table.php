<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tujuans', function (Blueprint $table) {
            $table->id();
            $table->string('kode_tujuan')->unique(); // TUJ-001, TUJ-002, dst
            $table->string('nama');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tujuans');
    }
};

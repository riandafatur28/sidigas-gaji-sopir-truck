<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            // Relasi eksplisit users -> otps (sebelumnya hanya lewat string email).
            // Nullable agar OTP untuk email yang belum terdaftar tetap bisa dibuat.
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};

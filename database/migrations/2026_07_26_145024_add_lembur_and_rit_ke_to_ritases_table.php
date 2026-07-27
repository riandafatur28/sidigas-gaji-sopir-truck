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
        Schema::table('ritases', function (Blueprint $table) {
            if (!Schema::hasColumn('ritases', 'is_lembur')) {
                $table->boolean('is_lembur')->default(false)->after('catatan');
            }
            if (!Schema::hasColumn('ritases', 'upah_lembur')) {
                $table->decimal('upah_lembur', 15, 2)->default(0)->after('is_lembur');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ritases', function (Blueprint $table) {
            if (Schema::hasColumn('ritases', 'is_lembur')) {
                $table->dropColumn('is_lembur');
            }
            if (Schema::hasColumn('ritases', 'upah_lembur')) {
                $table->dropColumn('upah_lembur');
            }
        });
    }
};

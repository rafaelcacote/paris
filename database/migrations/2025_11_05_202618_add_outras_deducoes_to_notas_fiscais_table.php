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
        Schema::connection('pgsql')->table('financeiro.notas_fiscais', function (Blueprint $table) {
            $table->decimal('outras_deducoes', 10, 2)->nullable()->after('issqn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql')->table('financeiro.notas_fiscais', function (Blueprint $table) {
            $table->dropColumn('outras_deducoes');
        });
    }
};

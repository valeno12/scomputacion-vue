<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->timestamp('cobrado_en')->nullable()->index()->after('fecha_cobro');
        });

        DB::table('operaciones')->whereNotNull('fecha_cobro')->whereNull('cobrado_en')
            ->update(['cobrado_en' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('operaciones', fn (Blueprint $table) => $table->dropColumn('cobrado_en'));
    }
};

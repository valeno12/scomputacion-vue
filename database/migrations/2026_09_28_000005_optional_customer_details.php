<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cliente', function (Blueprint $table) {
            foreach (['dni', 'mail', 'telefono', 'direccion'] as $column) {
                $table->string($column)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Keep nullable columns: restoring NOT NULL would invalidate newly created customers.
    }
};

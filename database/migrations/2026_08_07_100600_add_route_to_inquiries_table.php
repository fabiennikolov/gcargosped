<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            // The original offer form asked where the load is collected from
            // and delivered to — the two things a haulier prices on. They were
            // missing from the first cut of this table.
            $table->string('origin')->nullable()->after('cargo_type');
            $table->string('destination')->nullable()->after('origin');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn(['origin', 'destination']);
        });
    }
};

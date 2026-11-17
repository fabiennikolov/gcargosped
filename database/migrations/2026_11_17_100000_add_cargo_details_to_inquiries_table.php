<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            // What the office used to ask for in a follow-up call before it
            // could price a load: how many pieces, how they are packed, how
            // big and heavy they are, when they are ready and whether this is
            // a one-off. All optional — a form that demands them loses leads.
            $table->unsignedInteger('cargo_quantity')->nullable()->after('cargo_type');
            $table->string('cargo_packaging')->nullable()->after('cargo_quantity');
            $table->string('cargo_dimensions')->nullable()->after('cargo_packaging');
            $table->string('cargo_weight')->nullable()->after('cargo_dimensions');
            $table->date('loading_date')->nullable()->after('destination');
            $table->string('frequency')->nullable()->after('loading_date');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn([
                'cargo_quantity', 'cargo_packaging', 'cargo_dimensions',
                'cargo_weight', 'loading_date', 'frequency',
            ]);
        });
    }
};

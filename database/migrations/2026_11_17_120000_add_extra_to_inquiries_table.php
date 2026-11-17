<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            // Answers to the fields the admin adds to the forms themselves
            // (Форми), as [{label, value}] — the label is kept with the answer
            // so renaming or deleting the field later loses nothing.
            $table->json('extra')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn('extra');
        });
    }
};

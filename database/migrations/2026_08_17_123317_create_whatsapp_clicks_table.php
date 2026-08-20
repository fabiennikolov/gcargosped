<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_clicks', function (Blueprint $table) {
            $table->id();

            // Which menu option was tapped. Stored as the label itself rather
            // than an id, so renaming an option in the admin never orphans the
            // history that was recorded under the old wording.
            $table->string('topic');

            // The page the visitor was on — tells us where the question came
            // from, which the topic alone does not.
            $table->string('page')->nullable();

            $table->ipAddress('ip')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'topic']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_clicks');
    }
};

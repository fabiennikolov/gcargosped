<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('cargo_type')->nullable();
            $table->text('message')->nullable();

            // Which form it came from: 'offer', 'contact' or 'service'.
            $table->string('source')->default('offer');
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status')->default('new');
            $table->text('admin_note')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('body')->nullable();
            $table->string('image')->nullable();
            $table->text('icon_svg')->nullable();

            // Drives the public URL prefix, which is deliberately kept identical
            // to the old Webflow site so the indexed URLs keep working:
            //   is_main = true  -> /service/{slug}       (3 pages)
            //   is_main = false -> /sub-services/{slug}  (17 pages)
            $table->boolean('is_main')->default(false);

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();

            $table->index(['is_main', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};

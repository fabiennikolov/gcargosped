<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Blog posts were plain text, a blank line between paragraphs. The admin
     * now edits them in a rich editor that stores HTML, and that editor would
     * read the old text as one long paragraph. Each post written before is
     * turned into the same paragraphs it showed on the site.
     */
    public function up(): void
    {
        DB::table('posts')->select(['id', 'body'])->orderBy('id')->each(function ($post) {
            $body = trim((string) $post->body);

            if ($body === '' || str_starts_with($body, '<')) {
                return;
            }

            $html = collect(preg_split('/\n{2,}/', str_replace("\r\n", "\n", $body)))
                ->map(fn (string $paragraph) => trim($paragraph))
                ->filter()
                ->map(fn (string $paragraph) => '<p>'.nl2br(e($paragraph), false).'</p>')
                ->implode('');

            DB::table('posts')->where('id', $post->id)->update(['body' => $html]);
        });
    }

    public function down(): void
    {
        // The HTML keeps every word; going back would only lose formatting.
    }
};

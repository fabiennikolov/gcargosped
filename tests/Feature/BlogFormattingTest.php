<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Support\RichText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class BlogFormattingTest extends TestCase
{
    use RefreshDatabase;

    public function test_headings_bold_and_lists_reach_the_page(): void
    {
        $post = Post::create([
            'slug' => 'formatted', 'title' => 'С форматиране',
            'is_published' => true, 'published_at' => now()->subHour(),
            'body' => '<h2>Подзаглавие</h2><p>Текст с <strong>удебелено</strong>.</p><ul><li><p>Едно</p></li><li><p>Две</p></li></ul><ol><li><p>Първо</p></li></ol>',
        ]);

        $this->get("/blog/{$post->slug}")->assertInertia(
            fn (AssertableInertia $page) => $page->where(
                'post.body',
                '<h2>Подзаглавие</h2><p>Текст с <strong>удебелено</strong>.</p><ul><li><p>Едно</p></li><li><p>Две</p></li></ul><ol><li><p>Първо</p></li></ol>',
            ),
        );
    }

    public function test_plain_text_posts_still_show_their_paragraphs(): void
    {
        $this->assertSame('<p>Първи.</p><p>Втори &amp; ред.</p>', RichText::toHtml("Първи.\n\nВтори & ред."));
    }

    public function test_scripts_styles_and_foreign_attributes_are_removed(): void
    {
        $html = RichText::toHtml(
            '<p style="color:red" onclick="x()">Текст<script>alert(1)</script></p>'
            .'<div><span class="big">обвит</span></div>'
            .'<a href="javascript:alert(1)">лош</a><a href="https://example.com" target="_blank">добър</a>'
            .'<iframe src="https://evil.test"></iframe>',
        );

        $this->assertSame(
            '<p>Текст</p>обвит<a>лош</a><a href="https://example.com" rel="noopener">добър</a>',
            $html,
        );
    }
}

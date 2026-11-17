<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Turns a blog post body into the HTML the public page renders.
 *
 * The admin writes posts in a rich editor that stores HTML. Only what that
 * editor's toolbar can produce survives here — headings, bold, italic, lists,
 * links — every other tag is unwrapped to its text and every attribute but a
 * link's address is dropped, so a paste from Word or another site cannot
 * bring its styles, scripts or iframes onto the page.
 *
 * A body that is still plain text (a blank line between paragraphs, the way
 * posts were written before) is turned into paragraphs instead.
 */
class RichText
{
    private const ALLOWED = [
        'p', 'br', 'h2', 'h3', 'strong', 'b', 'em', 'i', 'u', 's',
        'ul', 'ol', 'li', 'a', 'blockquote',
    ];

    /** Elements dropped together with what is inside them. */
    private const REMOVED = ['script', 'style', 'iframe', 'object', 'embed', 'template', 'noscript'];

    public static function toHtml(?string $body): string
    {
        $body = trim((string) $body);

        if ($body === '') {
            return '';
        }

        if (! str_starts_with($body, '<')) {
            return self::fromPlainText($body);
        }

        return self::sanitize($body);
    }

    private static function fromPlainText(string $body): string
    {
        return collect(preg_split('/\n{2,}/', str_replace("\r\n", "\n", $body)))
            ->map(fn (string $paragraph) => trim($paragraph))
            ->filter()
            ->map(fn (string $paragraph) => '<p>'.nl2br(e($paragraph), false).'</p>')
            ->implode('');
    }

    private static function sanitize(string $html): string
    {
        $document = new DOMDocument;

        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="rich-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('rich-root');

        if (! $root) {
            return '';
        }

        self::clean($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $document->saveHTML($child);
        }

        return $out;
    }

    private static function clean(DOMNode $node): void
    {
        // Copied first: the list changes while elements are unwrapped.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::REMOVED, true)) {
                $node->removeChild($child);

                continue;
            }

            self::clean($child);

            if (! in_array($tag, self::ALLOWED, true)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            $href = $tag === 'a' ? trim($child->getAttribute('href')) : '';

            foreach (iterator_to_array($child->attributes) as $attribute) {
                $child->removeAttribute($attribute->nodeName);
            }

            if ($tag === 'a') {
                if (preg_match('~^(https?://|mailto:|tel:|/|#)~i', $href)) {
                    $child->setAttribute('href', $href);

                    if (preg_match('~^https?://~i', $href)) {
                        $child->setAttribute('rel', 'noopener');
                    }
                }
            }
        }
    }
}

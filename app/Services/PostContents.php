<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use Illuminate\Support\Str;

class PostContents
{
    /** @return array{html: string, headings: list<array{id: string, label: string}>} */
    public function build(string $html): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrorHandling = libxml_use_internal_errors(true);
        $encodedHtml = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8');
        $document->loadHTML('<div id="post-content-root">'.$encodedHtml.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorHandling);

        $root = $document->getElementById('post-content-root');
        if (! $root instanceof DOMElement) {
            return ['html' => $html, 'headings' => []];
        }

        $headings = [];
        $slugCounts = [];
        foreach ($root->getElementsByTagName('h2') as $heading) {
            $label = trim($heading->textContent);
            if ($label === '') {
                continue;
            }

            $baseSlug = Str::slug($label) ?: 'section';
            $slugCounts[$baseSlug] = ($slugCounts[$baseSlug] ?? 0) + 1;
            $id = $slugCounts[$baseSlug] === 1 ? $baseSlug : $baseSlug.'-'.$slugCounts[$baseSlug];
            $heading->setAttribute('id', $id);
            $headings[] = ['id' => $id, 'label' => $label];
        }

        $renderedHtml = '';
        foreach ($root->childNodes as $node) {
            $renderedHtml .= $document->saveHTML($node);
        }

        return ['html' => $renderedHtml, 'headings' => $headings];
    }
}

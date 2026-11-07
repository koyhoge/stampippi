<?php

namespace App\Services\StampStorage;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class SvgSanitizer
{
    /** @var array<int, string> */
    private const BLOCKED_TAGS = [
        'script',
        'foreignobject',
        'iframe',
        'frame',
        'frameset',
        'object',
        'embed',
        'applet',
        'audio',
        'video',
        'meta',
        'link',
        'base',
        'animate',
        'animatemotion',
        'animatetransform',
        'set',
        'handler',
        'listener',
    ];

    public static function isSvg(UploadedFile $file): bool
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: ($file->guessExtension() ?: ''));
        $mime = strtolower((string) $file->getMimeType());

        return $ext === 'svg' || str_contains($mime, 'svg');
    }

    /**
     * アップロードされたファイルが SVG の場合、危険な要素・属性を除去して上書きする
     */
    public static function sanitizeUploadedFile(UploadedFile $file): UploadedFile
    {
        if (! self::isSvg($file)) {
            return $file;
        }

        $raw = (string) file_get_contents($file->getRealPath());
        $clean = self::sanitizeString($raw);

        file_put_contents($file->getRealPath(), $clean);

        return $file;
    }

    public static function sanitizeString(string $svgContent): string
    {
        // DOCTYPE / ENTITY 宣言を含む SVG は XXE 防止のため拒否
        if (preg_match('/<!\s*(DOCTYPE|ENTITY)\b/i', $svgContent)) {
            throw ValidationException::withMessages([
                'stamp_icon_image' => ['SVGファイルに許可されていない宣言が含まれています。'],
            ]);
        }

        $dom = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML(
            $svgContent,
            LIBXML_NONET | LIBXML_NOENT | LIBXML_NOBLANKS | LIBXML_COMPACT
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if (! $loaded || ! $dom->documentElement || strtolower($dom->documentElement->localName ?? '') !== 'svg') {
            throw ValidationException::withMessages([
                'stamp_icon_image' => ['有効なSVGファイルとして読み込めませんでした。'],
            ]);
        }

        self::cleanNode($dom->documentElement);

        $output = $dom->saveXML($dom->documentElement);
        if (! is_string($output) || trim($output) === '') {
            throw ValidationException::withMessages([
                'stamp_icon_image' => ['SVGファイルのサニタイズに失敗しました。'],
            ]);
        }

        return $output;
    }

    private static function cleanNode(DOMNode $node): void
    {
        // 子ノードを逆順で走査して危険な要素や処理命令を除去
        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $child = $node->childNodes->item($i);
            if (! $child) {
                continue;
            }

            if ($child->nodeType === XML_PI_NODE || $child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
                continue;
            }

            if ($child instanceof DOMElement) {
                $tagName = strtolower($child->localName ?: $child->nodeName);
                if (in_array($tagName, self::BLOCKED_TAGS, true)) {
                    $node->removeChild($child);
                    continue;
                }

                self::cleanAttributes($child);
                self::cleanNode($child);
            }
        }

        if ($node instanceof DOMElement) {
            self::cleanAttributes($node);
        }
    }

    private static function cleanAttributes(DOMElement $element): void
    {
        if (! $element->hasAttributes()) {
            return;
        }

        $toRemove = [];
        foreach ($element->attributes as $attr) {
            $name = strtolower($attr->nodeName);
            $localName = strtolower($attr->localName ?: $attr->nodeName);
            $value = trim((string) $attr->nodeValue);
            $normalizedValue = preg_replace('/[\x00-\x20]+/', '', strtolower($value)) ?? '';

            // on* イベントハンドラ属性はすべて除去
            if (str_starts_with($name, 'on') || str_starts_with($localName, 'on')) {
                $toRemove[] = $attr->nodeName;
                continue;
            }

            // href / xlink:href / src 等の外部参照・javascript:/data: スキームを除去
            if (in_array($localName, ['href', 'src', 'action', 'formaction'], true)) {
                if (! str_starts_with($value, '#')) {
                    $toRemove[] = $attr->nodeName;
                    continue;
                }
            }

            // style 属性内の expression() / url(javascript:...) / @import 等を除去
            if ($localName === 'style') {
                if (
                    str_contains($normalizedValue, 'javascript:')
                    || str_contains($normalizedValue, 'vbscript:')
                    || str_contains($normalizedValue, 'data:')
                    || str_contains($normalizedValue, 'expression(')
                    || str_contains($normalizedValue, '@import')
                    || str_contains($normalizedValue, 'behavior:')
                ) {
                    $toRemove[] = $attr->nodeName;
                    continue;
                }
            }

            // 任意の属性値に javascript: / vbscript: が含まれる場合は除去
            if (
                str_contains($normalizedValue, 'javascript:')
                || str_contains($normalizedValue, 'vbscript:')
            ) {
                $toRemove[] = $attr->nodeName;
            }
        }

        foreach ($toRemove as $attrName) {
            $element->removeAttribute($attrName);
        }
    }
}


<?php

namespace App\Services\StampStorage;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class SvgSanitizer
{
    private const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';

    /** @var array<int, string> */
    private const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif',
        'svg',
    ];

    /** @var array<int, string> */
    private const ALLOWED_TAGS = [
        'svg',
        'g',
        'path',
        'circle',
        'ellipse',
        'line',
        'polyline',
        'polygon',
        'rect',
        'text',
        'tspan',
        'defs',
        'lineargradient',
        'radialgradient',
        'stop',
        'clippath',
        'mask',
        'pattern',
        'symbol',
        'title',
        'desc',
        'filter',
        'feblend',
        'fecolormatrix',
        'fecomponenttransfer',
        'fecomposite',
        'feconvolvematrix',
        'fediffuselighting',
        'fedisplacementmap',
        'fedropshadow',
        'feflood',
        'fefunca',
        'fefuncb',
        'fefuncg',
        'fefuncr',
        'fegaussianblur',
        'femerge',
        'femergenode',
        'femorphology',
        'feoffset',
        'fespecularlighting',
        'fetile',
        'feturbulence',
    ];

    public static function isSvg(UploadedFile $file): bool
    {
        $guessed = strtolower((string) $file->guessExtension());
        $clientExt = strtolower((string) $file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType());

        return $guessed === 'svg' || $clientExt === 'svg' || str_contains($mime, 'svg');
    }

    /**
     * クライアントのファイル名に依存せず、検証済みの安全な拡張子を決定する
     */
    public static function resolveSafeExtension(UploadedFile $file): string
    {
        if (self::isSvg($file)) {
            return 'svg';
        }

        $guessed = strtolower((string) $file->guessExtension());
        if (in_array($guessed, self::ALLOWED_EXTENSIONS, true)) {
            return $guessed === 'jpeg' ? 'jpg' : $guessed;
        }

        return 'png';
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

        $root = $dom->documentElement;
        if (! $loaded || ! $root || strtolower($root->localName ?: $root->nodeName) !== 'svg') {
            throw ValidationException::withMessages([
                'stamp_icon_image' => ['有効なSVGファイルとして読み込めませんでした。'],
            ]);
        }

        if ($root->namespaceURI !== null && $root->namespaceURI !== '' && $root->namespaceURI !== self::SVG_NAMESPACE) {
            throw ValidationException::withMessages([
                'stamp_icon_image' => ['SVGの名前空間が不正です。'],
            ]);
        }

        self::cleanNode($root);
        $root->setAttribute('xmlns', self::SVG_NAMESPACE);

        $output = $dom->saveXML($root);
        if (! is_string($output) || trim($output) === '') {
            throw ValidationException::withMessages([
                'stamp_icon_image' => ['SVGファイルのサニタイズに失敗しました。'],
            ]);
        }

        return $output;
    }

    private static function cleanNode(DOMNode $node): void
    {
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
                $ns = $child->namespaceURI;
                $tagName = strtolower($child->localName ?: $child->nodeName);

                // SVG 以外の名前空間 (XHTML / MathML 等) またはホワイトリスト外のタグはすべて除去
                if (
                    ($ns !== null && $ns !== '' && $ns !== self::SVG_NAMESPACE)
                    || ! in_array($tagName, self::ALLOWED_TAGS, true)
                ) {
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

            // on* イベントハンドラ属性、および外部参照属性 (href/src/xlink:href 等) はすべて除去
            if (
                str_starts_with($name, 'on')
                || str_starts_with($localName, 'on')
                || in_array($localName, ['href', 'src', 'action', 'formaction', 'ychannelselector', 'xchannelselector'], true)
            ) {
                $toRemove[] = $attr->nodeName;
                continue;
            }

            // style 属性または任意の属性値に url() / javascript: / data: / @import / expression() が含まれる場合は除去
            if (
                str_contains($normalizedValue, 'javascript:')
                || str_contains($normalizedValue, 'vbscript:')
                || str_contains($normalizedValue, 'data:')
                || str_contains($normalizedValue, 'expression(')
                || str_contains($normalizedValue, '@import')
                || str_contains($normalizedValue, 'behavior:')
                || ($localName === 'style' && str_contains($normalizedValue, 'url('))
            ) {
                $toRemove[] = $attr->nodeName;
            }
        }

        foreach ($toRemove as $attrName) {
            $element->removeAttribute($attrName);
        }
    }
}

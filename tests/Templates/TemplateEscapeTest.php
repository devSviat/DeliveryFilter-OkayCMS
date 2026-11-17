<?php

namespace Modules\Sviat\DeliveryFilter\Templates;

use Okay\Modules\Sviat\DeliveryFilter\Init\Init;
use PHPUnit\Framework\TestCase;

/**
 * Адмінка без autoescape, а bootstrap-select 1.11.2 вставляє як HTML title опції, data-subtext, data-content,
 * data-tokens і data-icon. Хаб не користується жодним з них; кожне виведене значення — з |escape.
 */
class TemplateEscapeTest extends TestCase
{
    /** @return array<string, string> */
    private static function sources(): array
    {
        $root = dirname((string)new \ReflectionClass(Init::class)->getFileName(), 2);
        $files = glob($root . '/Backend/design/html/*.tpl');
        self::assertCount(4, $files);
        $sources = array_combine(array_map('basename', $files), array_map('file_get_contents', $files));
        foreach (json_decode((string)file_get_contents($root . '/Init/module.json'), true)['modifications']['backend'] ?? [] as $modification) {
            foreach ($modification['changes'] as $i => $change) {
                if (isset($change['replace'])) {
                    $sources['module.json#' . $i] = $change['replace'];
                }
            }
        }

        return $sources;
    }

    public function testEveryPrintedValueIsEscaped(): void
    {
        $raw = [];
        foreach (self::sources() as $name => $source) {
            preg_match_all('/\{\$[^}]*\}/', $source, $m);
            foreach ($m[0] as $tag) {
                if (!str_contains($tag, '|escape') && !preg_match('/^\{\$[\w.>-]+\s*=/', $tag)) {
                    $raw[] = $name . ' ' . $tag;
                }
            }
        }

        self::assertSame([], $raw);
    }

    public function testNoAttributesThatBootstrapSelectInsertsAsHtml(): void
    {
        foreach (self::sources() as $name => $source) {
            foreach (['title=', 'data-subtext', 'data-content', 'data-tokens', 'data-icon'] as $attribute) {
                self::assertStringNotContainsString($attribute, $source, $name);
            }
        }
    }
}

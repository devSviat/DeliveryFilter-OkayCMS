<?php

namespace Modules\Sviat\DeliveryFilter\Templates;

use Okay\Modules\Sviat\DeliveryFilter\Init\Init;
use PHPUnit\Framework\TestCase;

/** Інформаційна сторінка хаба. Адмінка без autoescape: кожне значення з модулів — лише через |escape. */
class PageTemplateTest extends TestCase
{
    private const string XSS = '<img src=x onerror=alert(1)>';

    private static function render(array $carriers, array $hidden = []): string
    {
        $root = dirname((string)new \ReflectionClass(Init::class)->getFileName(), 2);
        $class = class_exists('Smarty\\Smarty') ? 'Smarty\\Smarty' : 'Smarty';
        $smarty = new $class();
        $smarty->setTemplateDir([$root . '/Backend/design/html']);
        $smarty->setCompileDir(sys_get_temp_dir() . '/delivery-filter-page-' . getmypid());
        $smarty->muteUndefinedOrNullWarnings();
        $smarty->assign('btr', new class {
            public function __get(string $key): string
            {
                return '[' . $key . ']';
            }
        });
        $_SESSION = ['id' => 'csrf-token'];
        $smarty->assign('delivery_filter_carriers', $carriers);
        $smarty->assign('delivery_filter_hidden', array_fill_keys($hidden, true));
        $smarty->assign('delivery_filter_toggles', array_filter(array_column($carriers, 'groups')) !== []);

        return $smarty->fetch('delivery_filter.tpl');
    }

    public function testStatesAndGroups(): void
    {
        $html = self::render([
            ['module' => 'Sviat/NovaPoshta', 'state' => 'connected', 'filter' => true, 'search' => false,
                'groups' => ['np' => ['title' => 'Нова Пошта', 'short' => 'НП', 'options' => ['arrived' => 'Прибула', 'none' => 'Без накладної']]]],
            ['module' => 'Sviat/Meest', 'state' => 'unsupported', 'filter' => false, 'search' => false, 'groups' => []],
        ]);

        self::assertStringContainsString('[sviat__delivery_filter__state_connected]', $html);
        self::assertStringContainsString('[sviat__delivery_filter__state_unsupported]', $html);
        self::assertStringContainsString('Прибула, Без накладної', $html);
        self::assertStringContainsString('<code>np</code>', $html);
        self::assertMatchesRegularExpression('~\[sviat__delivery_filter__list_filter\]: \[sviat__delivery_filter__yes\]\s*· \[sviat__delivery_filter__number_search\]: \[sviat__delivery_filter__no\]~', $html);
        self::assertStringNotContainsString('[sviat__delivery_filter__none]', $html);
    }

    public function testSwitchPerCarrier(): void
    {
        $group = ['title' => 'Т', 'short' => 'Т', 'options' => ['a' => 'А']];
        $html = self::render([
            ['module' => 'V/A', 'state' => 'connected', 'filter' => true, 'search' => true, 'groups' => ['np' => $group]],
            ['module' => 'V/B', 'state' => 'connected', 'filter' => true, 'search' => true, 'groups' => ['up' => $group]],
            ['module' => 'V/C', 'state' => 'unsupported', 'filter' => false, 'search' => false, 'groups' => []],
        ], ['up']);

        self::assertMatchesRegularExpression('~name="session_id"~', $html);
        self::assertSame(2, substr_count($html, 'name="carriers[]"'));
        self::assertMatchesRegularExpression('~name="visible\[\]" value="np"\s+checked~', $html);
        self::assertDoesNotMatchRegularExpression('~name="visible\[\]" value="up"\s+checked~', $html);
        self::assertStringContainsString('[sviat__delivery_filter__save]', $html);
        self::assertSame(1, substr_count($html, '[sviat__delivery_filter__state_hidden]'), 'only the hidden carrier');
        self::assertSame(1, substr_count($html, '[sviat__delivery_filter__state_connected]'));
    }

    public function testNoSwitchesNoSaveButton(): void
    {
        $html = self::render([['module' => 'V/C', 'state' => 'unsupported', 'filter' => false, 'search' => false, 'groups' => []]]);

        self::assertStringNotContainsString('visible[]', $html);
        self::assertStringNotContainsString('[sviat__delivery_filter__save]', $html);
    }

    public function testEmpty(): void
    {
        self::assertStringContainsString('[sviat__delivery_filter__none]', self::render([]));
    }

    public function testModuleValuesEscaped(): void
    {
        $html = self::render([['module' => self::XSS, 'state' => 'connected', 'filter' => true, 'search' => true,
            'groups' => [self::XSS => ['title' => self::XSS, 'short' => self::XSS, 'options' => ['a' => self::XSS]]]]]);

        self::assertStringNotContainsString('<img', $html);
        self::assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }
}

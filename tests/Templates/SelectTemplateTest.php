<?php

namespace Modules\Sviat\DeliveryFilter\Templates;

use Okay\Modules\Sviat\DeliveryFilter\Init\Init;
use PHPUnit\Framework\TestCase;

/** Select «Статус доставки», відрендерений Smarty; {url} замінено на запис переданих параметрів. */
class SelectTemplateTest extends TestCase
{
    private const array NP = ['title' => 'Нова Пошта', 'short' => 'НП', 'options' => ['arrived' => 'Прибула', 'none' => 'Без накладної']];
    private const array UP = ['title' => 'Укрпошта', 'short' => 'УП', 'options' => ['arrived' => 'У відділенні']];

    private static function render(string $file, array $vars): string
    {
        $root = dirname((string)new \ReflectionClass(Init::class)->getFileName(), 2);
        // Сток 4.5.3 має Smarty 4 (клас \Smarty), форк — Smarty 5 (\Smarty\Smarty); потрібні методи однакові.
        $class = class_exists('Smarty\\Smarty') ? 'Smarty\\Smarty' : 'Smarty';
        $smarty = new $class();
        $smarty->setTemplateDir([$root . '/Backend/design/html']);
        $smarty->setCompileDir(sys_get_temp_dir() . '/delivery-filter-render-' . getmypid());
        $smarty->muteUndefinedOrNullWarnings();
        $smarty->registerPlugin('function', 'url', static function (array $params): string {
            ksort($params);

            return 'URL[' . http_build_query(array_map(static fn($v) => $v ?? 'NULL', $params)) . ']';
        });
        $smarty->assign('btr', new class {
            public function __get(string $key): string
            {
                return '[' . $key . ']';
            }
        });
        foreach ($vars as $name => $value) {
            $smarty->assign($name, $value);
        }

        return $smarty->fetch($file);
    }

    public function testNoGroupsNoSelect(): void
    {
        self::assertSame('', trim(self::render('orders_delivery_select.tpl', ['delivery_status_filters' => [], 'delivery_status' => ''])));
    }

    public function testOneCarrierWithoutOptgroup(): void
    {
        $html = self::render('orders_delivery_select.tpl', ['delivery_status_filters' => ['np' => self::NP], 'delivery_status' => '']);

        self::assertStringContainsString('class="selectpicker form-control"', $html);
        self::assertStringContainsString('data-live-search="true"', $html);
        self::assertStringContainsString('onchange="location = this.value;"', $html);
        self::assertStringContainsString('aria-label="[sviat__delivery_filter__label]"', $html);
        self::assertStringNotContainsString('<optgroup', $html);
        self::assertMatchesRegularExpression('~<option value="URL\[delivery_status=NULL&page=NULL\]"\s+selected\s*>\[sviat__delivery_filter__all\]</option>~', $html);
        self::assertMatchesRegularExpression('~<option value="URL\[delivery_status=np%3Aarrived&page=NULL\]"\s*>НП · Прибула</option>~', $html);
        self::assertMatchesRegularExpression('~<option value="URL\[delivery_status=np%3Anone&page=NULL\]"\s*>НП · Без накладної</option>~', $html);
    }

    public function testSeveralCarriersInOptgroups(): void
    {
        $html = self::render('orders_delivery_select.tpl', ['delivery_status_filters' => ['np' => self::NP, 'up' => self::UP], 'delivery_status' => 'up:arrived']);

        self::assertSame(2, substr_count($html, '<optgroup'));
        self::assertSame(2, substr_count($html, '</optgroup>'));
        self::assertStringContainsString('<optgroup label="Нова Пошта">', $html);
        self::assertMatchesRegularExpression('~<option value="URL\[delivery_status=up%3Aarrived&page=NULL\]"\s+selected\s*>УП · У відділенні</option>~', $html);
        self::assertSame(1, preg_match_all('~\bselected\b~', $html));
    }

    /** DOM декодує атрибут один раз, а bootstrap-select вставляє label як HTML: потрібне подвійне екранування. */
    public function testOptgroupLabelIsEscapedTwice(): void
    {
        $html = self::render('orders_delivery_select.tpl', ['delivery_status_filters' => ['np' => ['title' => "П'ятий <b>"] + self::NP, 'up' => self::UP], 'delivery_status' => '']);

        self::assertStringContainsString('<optgroup label="П&amp;#039;ятий &amp;lt;b&amp;gt;">', $html);
    }

    public function testOptionTextIsEscaped(): void
    {
        $html = self::render('orders_delivery_select.tpl', ['delivery_status_filters' => ['np' => ['short' => '<i>', 'options' => ['arrived' => '<img src=x>']] + self::NP], 'delivery_status' => '']);

        self::assertStringContainsString('&lt;i&gt; · &lt;img src=x&gt;', $html);
        self::assertStringNotContainsString('<img', $html);
    }

    public function testHiddenInputsCarryTheStatus(): void
    {
        foreach (['orders_search_delivery.tpl', 'orders_date_delivery.tpl'] as $file) {
            self::assertSame('', trim(self::render($file, ['delivery_status' => ''])), $file);
            self::assertSame('<input type="hidden" name="delivery_status" value="np:arrived">', trim(self::render($file, ['delivery_status' => 'np:arrived'])), $file);
        }
    }
}

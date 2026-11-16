<?php

namespace Modules\Sviat\DeliveryFilter\Templates;

use Okay\Core\Config;
use Okay\Core\Modules\Module;
use Okay\Core\TplMod\Parser;
use Okay\Core\TplMod\TplMod;
use Okay\Modules\Sviat\DeliveryFilter\Init\Init;
use PHPUnit\Framework\TestCase;

/** orders.tpl адмінки після модифікацій хаба, так само як їх застосовує Design::applyTplModifiers(). */
class ModificationsTest extends TestCase
{
    private const array ANCHORS = [
        '{if $labels}',
        '<form class="search" method="get">',
        '<form class="box_date_filter fn_date_filter',
        '<input name="keyword" class="form-control"',
    ];

    /** Конструктор Module різний у стоку й форку: заглушка для кожного параметра. */
    private function module(): Module
    {
        $params = new \ReflectionMethod(Module::class, '__construct')->getParameters();

        return new Module(...array_map(fn(\ReflectionParameter $p): object => $this->createStub((string)$p->getType()), $params));
    }

    /** @return list<\Okay\Core\Modules\DTO\TplChangeDTO> */
    private function changes(): array
    {
        $root = dirname((string)new \ReflectionClass(Init::class)->getFileName(), 2);
        $changes = [];
        foreach ($this->module()->getModuleParams('Sviat', 'DeliveryFilter')->getBackendModifications() as $modification) {
            self::assertSame('orders.tpl', $modification->getFile());
            foreach ($modification->getChanges() as $change) {
                foreach (['Append', 'AppendBefore', 'Prepend', 'AppendAfter', 'Replace'] as $op) {
                    $value = $change->{'get' . $op}();
                    if ($value !== '' && is_file($root . '/Backend/design/html/' . $value)) {
                        $change->{'set' . $op}((string)file_get_contents($root . '/Backend/design/html/' . $value));
                    }
                }
                $changes[] = $change;
            }
        }

        return $changes;
    }

    private function modifiedOrdersTpl(): string
    {
        $tplMod = new TplMod(new Parser(), $this->createStub(Config::class));
        $tree = new Parser()->parse((string)file_get_contents(dirname(__DIR__, 5) . '/backend/design/html/orders.tpl'));
        new \ReflectionMethod(TplMod::class, 'walkByFile')->invoke($tplMod, $tree, $this->changes());

        return (string)new \ReflectionMethod(TplMod::class, 'build')->invoke($tplMod, $tree);
    }

    private static function form(string $html, string $open): string
    {
        $start = strpos($html, $open);
        self::assertNotFalse($start, $open);
        $end = strpos($html, '</form>', $start);
        self::assertNotFalse($end);

        return substr($html, $start, $end - $start);
    }

    public function testFourChangesOnTheSharedAnchors(): void
    {
        self::assertSame(self::ANCHORS, array_map(static fn($c): string => $c->getFind(), $this->changes()));
    }

    public function testEachAnchorMatchesOnce(): void
    {
        $orders = (string)file_get_contents(dirname(__DIR__, 5) . '/backend/design/html/orders.tpl');
        foreach (self::ANCHORS as $anchor) {
            self::assertSame(1, substr_count($orders, $anchor), $anchor);
        }
    }

    public function testSelectSitsBetweenStatusAndLabels(): void
    {
        $html = $this->modifiedOrdersTpl();
        $select = strpos($html, 'sviat__delivery_filter__label');

        self::assertNotFalse($select);
        self::assertGreaterThan(strpos($html, '{if $all_status}'), $select);
        self::assertLessThan(strpos($html, '{if $labels}'), $select);
    }

    public function testBothFormsCarryTheStatus(): void
    {
        $html = $this->modifiedOrdersTpl();
        $input = 'name="delivery_status" value="{$delivery_status|escape}"';

        self::assertStringContainsString($input, self::form($html, '<form class="search" method="get">'));
        self::assertStringContainsString($input, self::form($html, '<form class="box_date_filter fn_date_filter'));
        self::assertSame(2, substr_count($html, 'name="delivery_status"'));
    }

    public function testSearchPlaceholderWithoutHint(): void
    {
        $search = self::form($this->modifiedOrdersTpl(), '<form class="search" method="get">');

        self::assertStringContainsString('placeholder="{$btr->sviat__delivery_filter__search_placeholder|escape}"', $search);
        self::assertStringContainsString('value="{$keyword|escape}"', $search);
        self::assertStringNotContainsString('general_search', $search);
    }
}

<?php

namespace Modules\Sviat\DeliveryFilter;

require_once __DIR__ . '/Support/HubTestCase.php';

use Modules\Sviat\DeliveryFilter\Support\FakeCarrierA;
use Modules\Sviat\DeliveryFilter\Support\FakeCarrierB;
use Modules\Sviat\DeliveryFilter\Support\HubTestCase;
use Okay\Modules\Sviat\DeliveryFilter\Helpers\DeliveryFilterHelper;
use Okay\Modules\Sviat\DeliveryFilter\Helpers\DeliveryStatusParam;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * bootstrap-select 1.11.2 вставляє label optgroup як HTML, тож текст групи приходить у шаблон лише після
 * білого списку символів. Будь-яка вада в описі групи відкидає всю групу.
 */
class GroupSanitizeTest extends HubTestCase
{
    public function testCleanGroupsPass(): void
    {
        $this->connect(FakeCarrierA::class);
        $this->connect(FakeCarrierB::class);

        $filters = new DeliveryFilterHelper()->sanitizedFilters();

        self::assertSame(['aa', 'bb'], array_keys($filters));
        self::assertSame(FakeCarrierA::$group, $filters['aa']);
    }

    /** @return array<string, array{callable(array): array}> */
    public static function malformed(): array
    {
        return [
            'HTML у назві' => [static fn(array $g): array => ['title' => '<img src=x onerror=alert(1)>'] + $g],
            'HTML у короткій назві' => [static fn(array $g): array => ['short' => 'П"А'] + $g],
            'HTML у підписі статусу' => [static fn(array $g): array => ['options' => ['arrived' => '<b>Прибула</b>']] + $g],
            'амперсанд у назві' => [static fn(array $g): array => ['title' => 'А &amp; Б'] + $g],
            'ключ статусу з великими літерами' => [static fn(array $g): array => ['options' => ['Arrived' => 'Прибула']] + $g],
            'ключ статусу з лапкою' => [static fn(array $g): array => ['options' => ["arrived'" => 'Прибула']] + $g],
            'без статусів' => [static fn(array $g): array => ['options' => []] + $g],
            'без назви' => [static function (array $g): array {
                unset($g['title']);

                return $g;
            }],
            'назва не рядок' => [static fn(array $g): array => ['title' => ['Перевізник']] + $g],
            'legacy_param з лапкою' => [static fn(array $g): array => ['legacy_param' => "aa'"] + $g],
            'задовга назва' => [static fn(array $g): array => ['title' => str_repeat('а', 65)] + $g],
        ];
    }

    #[DataProvider('malformed')]
    public function testMalformedGroupIsDropped(callable $malform): void
    {
        FakeCarrierA::$group = $malform(FakeCarrierA::$group);
        $this->connect(FakeCarrierA::class);
        $this->connect(FakeCarrierB::class);

        self::assertSame(['bb'], array_keys(new DeliveryFilterHelper()->sanitizedFilters()));
    }

    public function testCarrierKeyMustBeLowercase(): void
    {
        $this->connect(Support\UppercaseCarrier::class);

        self::assertSame([], new DeliveryFilterHelper()->sanitizedFilters());
    }

    public function testRealStatusLabelsPass(): void
    {
        FakeCarrierA::$group['options'] = ['changed' => 'Змінено / невдала доставка', 'problem' => 'Проблема з врученням', 'q' => "Пам'ятка (2)"];
        $this->connect(FakeCarrierA::class);

        self::assertSame(['aa'], array_keys(new DeliveryFilterHelper()->sanitizedFilters()));
    }

    /** PHP робить ключ «123» цілим числом; такий статус лишається статусом, а не причиною відкинути групу. */
    public function testDigitOnlyStatusKeySurvives(): void
    {
        FakeCarrierA::$group['options'] = ['123' => 'Код сто двадцять три', 'arrived' => 'Прибула'];
        $this->connect(FakeCarrierA::class);

        $filters = new DeliveryFilterHelper()->sanitizedFilters();

        self::assertSame(['123', 'arrived'], array_map(strval(...), array_keys($filters['aa']['options'])));
        self::assertSame(['aa', '123'], DeliveryStatusParam::parse('aa:123', $filters));
    }
}

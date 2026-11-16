<?php

namespace Modules\Sviat\DeliveryFilter;

use Okay\Modules\Sviat\DeliveryFilter\Helpers\DeliveryStatusParam;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DeliveryStatusParamTest extends TestCase
{
    private const array FILTERS = [
        'np' => ['title' => 'Нова Пошта', 'short' => 'НП', 'options' => ['arrived' => 'Прибула', 'none' => 'Без накладної']],
        'up' => ['title' => 'Укрпошта', 'short' => 'УП', 'options' => ['arrived' => 'У відділенні']],
    ];

    public function testKnownPairIsParsed(): void
    {
        self::assertSame(['np', 'arrived'], DeliveryStatusParam::parse('np:arrived', self::FILTERS));
        self::assertSame(['up', 'arrived'], DeliveryStatusParam::parse('up:arrived', self::FILTERS));
    }

    /** @return array<string, array{mixed}> */
    public static function rejected(): array
    {
        return [
            'невідомий статус' => ['np:nope'],
            'невідомий перевізник' => ['xx:arrived'],
            'SQL-хвіст' => ["np:arrived'--"],
            'порожньо' => [''],
            'без двокрапки' => ['nparrived'],
            'великі літери' => ['NP:arrived'],
            'масив' => [['np:arrived']],
            'null' => [null],
            'статус чужого перевізника' => ['up:none'],
        ];
    }

    #[DataProvider('rejected')]
    public function testAnythingElseIsIgnored(mixed $raw): void
    {
        self::assertNull(DeliveryStatusParam::parse($raw, self::FILTERS));
    }
}

<?php

namespace Modules\Sviat\DeliveryFilter;

require_once __DIR__ . '/Support/HubTestCase.php';

use Modules\Sviat\DeliveryFilter\Support\FakeCarrierA;
use Modules\Sviat\DeliveryFilter\Support\FakeCarrierB;
use Modules\Sviat\DeliveryFilter\Support\HubTestCase;

/** Кожен перевізник упізнає лише свій формат номера; хаб бере об'єднання знайдених id. */
class ShipmentSearchTest extends HubTestCase
{
    protected function setUp(): void
    {
        $this->connect(FakeCarrierA::class);
        $this->connect(FakeCarrierB::class);
    }

    public function testUnionOfCarriers(): void
    {
        FakeCarrierA::$found = [3, 5];
        FakeCarrierB::$found = [5, 9];

        $filter = $this->extender(['keyword' => ' 20 4512 '])->buildFilter(['keyword' => ' 20 4512 ', 'page' => 1]);

        self::assertSame(['page' => 1, 'id' => [3, 5, 9]], $filter);
        self::assertSame(' 20 4512 ', $this->assigned['keyword']);
    }

    public function testExistingIdNarrowsTheResult(): void
    {
        FakeCarrierA::$found = [3, 5];
        FakeCarrierB::$found = [9];

        $filter = $this->extender(['keyword' => 'x'])->buildFilter(['keyword' => 'x', 'id' => [5, 7]]);

        self::assertSame([5], $filter['id']);
    }

    public function testEmptyIntersectionMatchesNothing(): void
    {
        FakeCarrierA::$found = [3];

        $filter = $this->extender(['keyword' => 'x'])->buildFilter(['keyword' => 'x', 'id' => [7]]);

        // Порожній список id означав би для фільтра ядра «без умови».
        self::assertSame([0], $filter['id']);
        self::assertArrayNotHasKey('keyword', $filter);
    }

    public function testNothingFoundKeepsTheCoreSearch(): void
    {
        $filter = $this->extender(['keyword' => 'Петренко'])->buildFilter(['keyword' => 'Петренко']);

        self::assertSame(['keyword' => 'Петренко'], $filter);
        self::assertArrayNotHasKey('keyword', $this->assigned);
    }

    public function testJunkIdsFromACarrierAreDropped(): void
    {
        FakeCarrierA::$found = ['4', 0, -2, '4'];

        $filter = $this->extender(['keyword' => 'x'])->buildFilter(['keyword' => 'x']);

        self::assertSame([4], $filter['id']);
    }

    public function testSearchAndStatusTogether(): void
    {
        FakeCarrierA::$found = [3];

        $filter = $this->extender(['keyword' => 'x', 'delivery_status' => 'bb:arrived'])->buildFilter(['keyword' => 'x']);

        self::assertSame(['bb_group' => 'arrived', 'id' => [3]], $filter);
    }
}

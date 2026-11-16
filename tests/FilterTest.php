<?php

namespace Modules\Sviat\DeliveryFilter;

require_once __DIR__ . '/Support/HubTestCase.php';

use Modules\Sviat\DeliveryFilter\Support\FakeCarrierA;
use Modules\Sviat\DeliveryFilter\Support\FakeCarrierB;
use Modules\Sviat\DeliveryFilter\Support\HubTestCase;

class FilterTest extends HubTestCase
{
    protected function setUp(): void
    {
        $this->connect(FakeCarrierA::class);
        $this->connect(FakeCarrierB::class);
    }

    public function testKnownStatusReachesTheCarrier(): void
    {
        $filter = $this->extender(['delivery_status' => 'aa:arrived'])->buildFilter(['page' => 1, 'limit' => 40]);

        self::assertSame([['aa', 'arrived']], FakeCarrierA::$calls);
        self::assertSame(['page' => 1, 'limit' => 40, 'aa_group' => 'arrived'], $filter);
        self::assertSame('aa:arrived', $this->assigned['delivery_status']);
        self::assertSame(['aa', 'bb'], array_keys($this->assigned['delivery_status_filters']));
    }

    public function testOtherCarrierGetsItsOwn(): void
    {
        $filter = $this->extender(['delivery_status' => 'bb:arrived'])->buildFilter(['page' => 1]);

        self::assertSame(['page' => 1, 'bb_group' => 'arrived'], $filter);
    }

    public function testUnknownValueLeavesTheFilterAlone(): void
    {
        foreach (['aa:nope', 'xx:arrived', "aa:arrived'--", 'bb:none'] as $raw) {
            $this->assigned = [];
            $filter = $this->extender(['delivery_status' => $raw])->buildFilter(['page' => 1]);

            self::assertSame(['page' => 1], $filter, $raw);
            self::assertSame('', $this->assigned['delivery_status'], $raw);
        }
        self::assertSame([], FakeCarrierA::$calls);
        self::assertSame([], $this->redirects);
    }

    public function testNoCarriersNoSelect(): void
    {
        $this->tearDown();

        $filter = $this->extender(['delivery_status' => 'aa:arrived'])->buildFilter(['page' => 1]);

        self::assertSame(['page' => 1], $filter);
        self::assertSame([], $this->assigned['delivery_status_filters']);
    }
}

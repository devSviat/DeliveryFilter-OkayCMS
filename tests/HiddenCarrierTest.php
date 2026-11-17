<?php

namespace Modules\Sviat\DeliveryFilter;

require_once __DIR__ . '/Support/HubTestCase.php';

use Modules\Sviat\DeliveryFilter\Support\FakeCarrierA;
use Modules\Sviat\DeliveryFilter\Support\FakeCarrierB;
use Modules\Sviat\DeliveryFilter\Support\HubTestCase;

/** Перевізник, вимкнений перемикачем на сторінці модуля. */
class HiddenCarrierTest extends HubTestCase
{
    protected function setUp(): void
    {
        $this->connect(FakeCarrierA::class);
        $this->connect(FakeCarrierB::class);
        $this->hiddenSetting = '["aa"]';
    }

    public function testGroupLeavesTheSelect(): void
    {
        $this->extender([])->buildFilter(['page' => 1]);

        self::assertSame(['bb'], array_keys($this->assigned['delivery_status_filters']));
    }

    public function testItsStatusIsIgnoredInListAndCounters(): void
    {
        $extender = $this->extender(['delivery_status' => 'aa:arrived']);

        self::assertSame(['page' => 1], $extender->buildFilter(['page' => 1]));
        self::assertSame(['status' => 1], $extender->countFilter(['status' => 1], ['page' => 1]));
        self::assertSame([], FakeCarrierA::$calls);
        self::assertSame('', $this->assigned['delivery_status']);
    }

    public function testOldBookmarkNotRedirected(): void
    {
        $this->extender(['aa_status' => 'arrived'])->buildFilter(['page' => 1]);

        self::assertSame([], $this->redirects);
    }

    public function testShipmentSearchStillWorks(): void
    {
        FakeCarrierA::$found = [42];

        self::assertSame([42], $this->extender(['keyword' => 'AA-42'])->buildFilter(['keyword' => 'AA-42'])['id']);
    }

    public function testOtherCarrierUntouched(): void
    {
        self::assertSame(['page' => 1, 'bb_group' => 'arrived'], $this->extender(['delivery_status' => 'bb:arrived'])->buildFilter(['page' => 1]));
    }

    public function testUnreadableSettingHidesNothing(): void
    {
        foreach (['not json', '{"a":', '[1, null, "<b>", "AA"]', ''] as $raw) {
            $this->hiddenSetting = $raw;
            $this->assigned = [];
            $this->extender([])->buildFilter(['page' => 1]);

            self::assertSame(['aa', 'bb'], array_keys($this->assigned['delivery_status_filters']), $raw);
        }
    }
}

<?php

namespace Modules\Sviat\DeliveryFilter;

require_once __DIR__ . '/Support/HubTestCase.php';

use Modules\Sviat\DeliveryFilter\Support\FakeCarrierA;
use Modules\Sviat\DeliveryFilter\Support\HubTestCase;

/** Лічильники статусів угорі сторінки рахують ті самі замовлення, що й список, лише без статусу замовлення. */
class CountFilterTest extends HubTestCase
{
    protected function setUp(): void
    {
        $this->connect(FakeCarrierA::class);
    }

    public function testStatusAndSearchCarryOver(): void
    {
        FakeCarrierA::$found = [3];
        $get = ['keyword' => 'x', 'delivery_status' => 'aa:none'];
        $hub = $this->extender($get);
        $filter = $hub->buildFilter(['keyword' => 'x', 'status_id' => 2]);

        $count = $hub->countFilter(['keyword' => 'x'], $filter);

        self::assertSame(['id' => [3], 'aa_group' => 'none'], $count);
    }

    public function testCoreSearchStays(): void
    {
        $hub = $this->extender(['keyword' => 'Петренко']);
        $filter = $hub->buildFilter(['keyword' => 'Петренко']);

        self::assertSame(['keyword' => 'Петренко'], $hub->countFilter(['keyword' => 'Петренко'], $filter));
    }

    public function testIdFromAnotherModuleKeepsTheKeyword(): void
    {
        $hub = $this->extender([]);

        self::assertSame(['keyword' => 'x', 'id' => [7]], $hub->countFilter(['keyword' => 'x'], ['keyword' => 'x', 'id' => [7]]));
    }

    public function testInvalidStatusIsNotApplied(): void
    {
        $hub = $this->extender(['delivery_status' => 'aa:nope']);

        self::assertSame([], $hub->countFilter([], []));
    }
}

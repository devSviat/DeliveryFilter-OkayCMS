<?php

namespace Modules\Sviat\DeliveryFilter;

require_once __DIR__ . '/Support/HubTestCase.php';

use Modules\Sviat\DeliveryFilter\Support\FakeCarrierA;
use Modules\Sviat\DeliveryFilter\Support\HubTestCase;

/**
 * Адмінка запускає init() усіх встановлених модулів, і вимкнених теж (Modules::startAllModules), тож ланки
 * вимкненого хаба зареєстровані. Вони мусять мовчати: перевізники тоді ведуть свої старі фільтри.
 */
class HubDisabledTest extends HubTestCase
{
    protected function setUp(): void
    {
        $this->connect(FakeCarrierA::class);
        $this->hubEnabled = false;
    }

    public function testListIsUntouched(): void
    {
        FakeCarrierA::$found = [3];
        $hub = $this->extender(['delivery_status' => 'aa:arrived', 'keyword' => 'x', 'aa_status' => 'none']);

        self::assertSame(['keyword' => 'x'], $hub->buildFilter(['keyword' => 'x']));
        self::assertSame([], $this->redirects);
        self::assertSame([], $this->assigned);
        self::assertSame([], FakeCarrierA::$calls);
    }

    public function testCountsAreUntouched(): void
    {
        $hub = $this->extender(['delivery_status' => 'aa:arrived']);

        self::assertSame(['keyword' => 'x'], $hub->countFilter(['keyword' => 'x'], ['id' => [3]]));
    }
}

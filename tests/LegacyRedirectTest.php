<?php

namespace Modules\Sviat\DeliveryFilter;

require_once __DIR__ . '/Support/HubTestCase.php';

use Modules\Sviat\DeliveryFilter\Support\FakeCarrierA;
use Modules\Sviat\DeliveryFilter\Support\FakeCarrierB;
use Modules\Sviat\DeliveryFilter\Support\HubTestCase;

/** Закладки менеджерів зі старим параметром перевізника ведуть на канонічну адресу хаба. */
class LegacyRedirectTest extends HubTestCase
{
    protected function setUp(): void
    {
        $this->connect(FakeCarrierA::class);
        $this->connect(FakeCarrierB::class);
    }

    public function testOldBookmarkRedirects(): void
    {
        $this->extender(['controller' => 'OrdersAdmin', 'aa_status' => 'arrived', 'page' => '2'])->buildFilter(['page' => 2]);

        self::assertCount(1, $this->redirects);
        $query = [];
        parse_str((string)parse_url($this->redirects[0], PHP_URL_QUERY), $query);
        self::assertSame(['controller' => 'OrdersAdmin', 'page' => '2', 'delivery_status' => 'aa:arrived'], $query);
        self::assertStringStartsWith('/backend/index.php?', $this->redirects[0]);
    }

    public function testUnknownLegacyValueStays(): void
    {
        $filter = $this->extender(['aa_status' => 'nope'])->buildFilter(['page' => 1]);

        self::assertSame([], $this->redirects);
        self::assertSame(['page' => 1], $filter);
    }

    public function testCanonicalValueWins(): void
    {
        $this->extender(['aa_status' => 'arrived', 'delivery_status' => 'bb:arrived'])->buildFilter([]);

        self::assertSame([], $this->redirects);
    }

    public function testParamOfAGroupWithoutLegacyIsIgnored(): void
    {
        $this->extender(['bb_status' => 'arrived'])->buildFilter([]);

        self::assertSame([], $this->redirects);
    }

    public function testPostIsNotRedirected(): void
    {
        $this->extender(['aa_status' => 'arrived'], 'POST')->buildFilter([]);

        self::assertSame([], $this->redirects);
    }
}

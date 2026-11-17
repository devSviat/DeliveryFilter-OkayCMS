<?php

namespace Modules\Sviat\DeliveryFilter\Backend;

use Okay\Modules\Sviat\DeliveryFilter\Backend\CarrierOverview;
use PHPUnit\Framework\TestCase;

class CarrierOverviewTest extends TestCase
{
    private const array GROUP = ['title' => 'Нова Пошта', 'short' => 'НП', 'options' => ['arrived' => 'Прибула']];

    private static function adapter(mixed $groups, bool $filter = true, bool $search = true): array
    {
        return ['groups' => $groups, 'filter' => $filter, 'search' => $search];
    }

    /** @return array<string, array{state: string, groups: array}> */
    private static function states(array $adapters, array $delivery = [], array $inactive = []): array
    {
        $rows = CarrierOverview::rows($adapters, $delivery, static fn(string $m): bool => !in_array($m, $inactive, true));

        return array_column($rows, null, 'module');
    }

    public function testEveryState(): void
    {
        $rows = self::states([
            'V/Connected' => self::adapter(['np' => self::GROUP]),
            'V/Disabled' => self::adapter([]),
            'V/Silent' => self::adapter([]),
            'V/Rejected' => self::adapter(['np' => self::GROUP, 'BAD' => self::GROUP]),
            'V/Error' => self::adapter(new \RuntimeException('x')),
        ], ['V/Plain' => true, 'V/Connected' => true], ['V/Disabled']);

        self::assertSame(['V/Connected', 'V/Disabled', 'V/Error', 'V/Plain', 'V/Rejected', 'V/Silent'], array_keys($rows));
        self::assertSame(CarrierOverview::CONNECTED, $rows['V/Connected']['state']);
        self::assertSame(['np' => self::GROUP], $rows['V/Connected']['groups']);
        self::assertSame(CarrierOverview::DISABLED, $rows['V/Disabled']['state']);
        self::assertSame(CarrierOverview::SILENT, $rows['V/Silent']['state']);
        self::assertSame(CarrierOverview::REJECTED, $rows['V/Rejected']['state']);
        self::assertSame(['np'], array_keys($rows['V/Rejected']['groups']), 'only the clean group is shown');
        self::assertSame(CarrierOverview::ERROR, $rows['V/Error']['state']);
        self::assertSame(CarrierOverview::UNSUPPORTED, $rows['V/Plain']['state']);
    }

    public function testDisabledModuleShowsNoGroupsEvenIfItAnswers(): void
    {
        $rows = self::states(['V/Off' => self::adapter(['np' => self::GROUP])], [], ['V/Off']);

        self::assertSame(CarrierOverview::DISABLED, $rows['V/Off']['state']);
        self::assertSame([], $rows['V/Off']['groups']);
    }

    public function testModuleOfClass(): void
    {
        self::assertSame('Sviat/NovaPoshta', CarrierOverview::moduleOf('Okay\\Modules\\Sviat\\NovaPoshta\\Extenders\\DeliveryFilterExtender'));
        self::assertSame('Sviat/NovaPoshta', CarrierOverview::moduleOf('\\Okay\\Modules\\Sviat\\NovaPoshta\\X'));
        self::assertNull(CarrierOverview::moduleOf('Okay\\Admin\\Helpers\\BackendOrdersHelper'));
        self::assertNull(CarrierOverview::moduleOf('Okay\\Modules\\Sviat'));
    }
}

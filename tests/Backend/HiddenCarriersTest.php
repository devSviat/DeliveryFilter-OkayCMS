<?php

namespace Modules\Sviat\DeliveryFilter\Backend;

use Okay\Core\Settings;
use Okay\Modules\Sviat\DeliveryFilter\Backend\Controllers\DeliveryFilterAdmin;
use Okay\Modules\Sviat\DeliveryFilter\Helpers\HiddenCarriers;
use PHPUnit\Framework\TestCase;

class HiddenCarriersTest extends TestCase
{
    private array $stored = [];

    private function hidden(): HiddenCarriers
    {
        $settings = $this->createStub(Settings::class);
        $settings->method('get')->willReturnCallback(fn(string $n) => $this->stored[$n] ?? null);
        $settings->method('set')->willReturnCallback(function (string $n, $v): void {
            $this->stored[$n] = $v;
        });

        return new HiddenCarriers($settings);
    }

    public function testSavedAsJsonStringAndReadBack(): void
    {
        $this->hidden()->save(['up', 'np', 'np', 'BAD', '<b>', 7, ['x']]);

        self::assertSame('["np","up"]', $this->stored[HiddenCarriers::SETTING]);
        self::assertSame(['np', 'up'], $this->hidden()->all());
        self::assertSame(['meest' => 1], $this->hidden()->visible(['np' => 1, 'meest' => 1, 'up' => 1]));
    }

    public function testFormPostGivesUncheckedKeys(): void
    {
        self::assertSame(['up'], DeliveryFilterAdmin::hiddenFromPost(['np', 'up'], ['np']));
        self::assertSame(['np', 'up'], DeliveryFilterAdmin::hiddenFromPost(['np', 'up'], null));
        self::assertSame([], DeliveryFilterAdmin::hiddenFromPost(['np'], ['np', 'up']));
        self::assertSame([], DeliveryFilterAdmin::hiddenFromPost('np', ['np']));
        self::assertSame(['np'], DeliveryFilterAdmin::hiddenFromPost(['np', ['x']], [['y']]));
    }
}

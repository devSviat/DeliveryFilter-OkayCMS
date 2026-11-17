<?php

namespace Modules\Sviat\DeliveryFilter\Backend;

require_once __DIR__ . '/../Support/FakeModuleAdapters.php';

use Okay\Core\EntityFactory;
use Okay\Core\Modules\Extender\ChainExtender;
use Okay\Core\Modules\Modules;
use Okay\Entities\ModulesEntity;
use Okay\Modules\FakeVendor\Alpha\Extenders\HubLinks as Alpha;
use Okay\Modules\FakeVendor\Beta\Extenders\HubLinks as Beta;
use Okay\Modules\FakeVendor\Gamma\Extenders\HubLinks as Gamma;
use Okay\Modules\Sviat\DeliveryFilter\Backend\CarrierOverview;
use Okay\Modules\Sviat\DeliveryFilter\Backend\HubAdapters;
use Okay\Modules\Sviat\DeliveryFilter\Helpers\DeliveryFilterHelper;
use PHPUnit\Framework\TestCase;

/** Ланки зареєстровані через справжній ChainExtender, як це робить Init перевізника. */
class HubAdaptersTest extends TestCase
{
    private const array TRIGGERS = ['findDeliveryStatusFilters', 'filterByDeliveryStatus', 'findOrderIdsByShipmentNumber'];

    protected function tearDown(): void
    {
        $triggers = array_map(static fn(string $method): string => DeliveryFilterHelper::class . '::' . $method, self::TRIGGERS);
        \Closure::bind(static function () use ($triggers): void {
            foreach ($triggers as $trigger) {
                unset(ChainExtender::$triggers[$trigger]);
            }
        }, null, ChainExtender::class)();
    }

    public function testEachModuleSeenSeparately(): void
    {
        $chain = new ChainExtender();
        $chain->newExtension(DeliveryFilterHelper::class, 'findDeliveryStatusFilters', Alpha::class, 'groups');
        $chain->newExtension(DeliveryFilterHelper::class, 'filterByDeliveryStatus', Alpha::class, 'filter');
        $chain->newExtension(DeliveryFilterHelper::class, 'findOrderIdsByShipmentNumber', Alpha::class, 'search');
        $chain->newExtension(DeliveryFilterHelper::class, 'findDeliveryStatusFilters', Beta::class, 'groups');
        $chain->newExtension(DeliveryFilterHelper::class, 'findDeliveryStatusFilters', Gamma::class, 'groups');

        $entity = $this->createStub(ModulesEntity::class);
        $entity->method('noLimit')->willReturnSelf();
        $entity->method('find')->willReturn([
            (object)['vendor' => 'FakeVendor', 'module_name' => 'Alpha', 'enabled' => 1],
            (object)['vendor' => 'FakeVendor', 'module_name' => 'Delta', 'enabled' => 0],
        ]);
        $entities = $this->createStub(EntityFactory::class);
        $entities->method('get')->willReturn($entity);
        $modules = $this->createStub(Modules::class);
        $modules->method('isActiveModule')->willReturn(true);

        $rows = array_column(new HubAdapters($entities, $modules)->overview(), null, 'module');

        self::assertSame(['FakeVendor/Alpha', 'FakeVendor/Beta', 'FakeVendor/Delta', 'FakeVendor/Gamma'], array_keys($rows));
        self::assertSame(CarrierOverview::CONNECTED, $rows['FakeVendor/Alpha']['state']);
        self::assertSame(['al'], array_keys($rows['FakeVendor/Alpha']['groups']), 'Alpha gets only its own group');
        self::assertTrue($rows['FakeVendor/Alpha']['filter']);
        self::assertTrue($rows['FakeVendor/Alpha']['search']);
        self::assertSame(CarrierOverview::REJECTED, $rows['FakeVendor/Beta']['state']);
        self::assertFalse($rows['FakeVendor/Beta']['filter']);
        self::assertSame(CarrierOverview::ERROR, $rows['FakeVendor/Gamma']['state']);
        self::assertSame(CarrierOverview::UNSUPPORTED, $rows['FakeVendor/Delta']['state']);
    }
}

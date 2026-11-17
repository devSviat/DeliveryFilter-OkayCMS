<?php

namespace Okay\Modules\Sviat\DeliveryFilter\Backend;

use Okay\Core\EntityFactory;
use Okay\Core\Modules\Extender\ExtenderFacade;
use Okay\Core\Modules\Modules;
use Okay\Core\ServiceLocator;
use Okay\Entities\ModulesEntity;
use Okay\Modules\Sviat\DeliveryFilter\Helpers\DeliveryFilterHelper;

/** Збирає для інформаційної сторінки, які модулі під'єднані до хаба; лише читає. */
class HubAdapters
{
    public function __construct(
        private readonly EntityFactory $entityFactory,
        private readonly Modules $modules,
    ) {
    }

    /** @return list<array{module: string, state: string, groups: array, filter: bool, search: bool}> */
    public function overview(): array
    {
        $filter = $this->linkedModules('filterByDeliveryStatus');
        $search = $this->linkedModules('findOrderIdsByShipmentNumber');
        $adapters = [];
        foreach ($this->links('findDeliveryStatusFilters') as $link) {
            $module = CarrierOverview::moduleOf($link->class);
            if ($module === null) {
                continue;
            }
            $adapters[$module] = [
                'groups' => $this->ownGroups($link),
                'filter' => isset($filter[$module]),
                'search' => isset($search[$module]),
            ];
        }

        return CarrierOverview::rows($adapters, $this->deliveryModules(), function (string $module): bool {
            [$vendor, $name] = explode('/', $module, 2);

            return (bool)$this->modules->isActiveModule($vendor, $name);
        });
    }

    /**
     * Ланку модуля викликаємо окремо від решти ланцюжка: інакше не видно, чия саме група прийшла.
     * Екземпляр — так само, як його бере ChainExtender ядра.
     */
    private function ownGroups(object $link): mixed
    {
        try {
            $locator = ServiceLocator::getInstance();
            $extender = $locator->hasService($link->class) ? $locator->getService($link->class) : new ($link->class)();

            return $extender->{$link->method}([]);
        } catch (\Throwable $e) {
            return $e;
        }
    }

    /** @return array<string, true> */
    private function linkedModules(string $method): array
    {
        $modules = [];
        foreach ($this->links($method) as $link) {
            $module = CarrierOverview::moduleOf($link->class);
            if ($module !== null) {
                $modules[$module] = true;
            }
        }

        return $modules;
    }

    /** @return list<object{class: string, method: string}> */
    private function links(string $method): array
    {
        $links = ExtenderFacade::chainExtLog([DeliveryFilterHelper::class, $method]);

        return array_values(array_filter(is_array($links) ? $links : [], static fn($l): bool => is_object($l) && is_string($l->class ?? null) && is_string($l->method ?? null)));
    }

    /** @return array<string, bool> */
    private function deliveryModules(): array
    {
        $modules = [];
        foreach ($this->entityFactory->get(ModulesEntity::class)->noLimit()->find(['type' => MODULE_TYPE_DELIVERY]) as $module) {
            $modules[$module->vendor . '/' . $module->module_name] = (bool)$module->enabled;
        }

        return $modules;
    }
}

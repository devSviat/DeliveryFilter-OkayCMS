<?php

namespace Okay\Modules\Sviat\DeliveryFilter\Extenders;

use Okay\Core\Design;
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Core\Modules\Modules;
use Okay\Core\Request;
use Okay\Modules\Sviat\DeliveryFilter\Helpers\DeliveryFilterHelper;
use Okay\Modules\Sviat\DeliveryFilter\Helpers\DeliveryStatusParam;
use Okay\Modules\Sviat\DeliveryFilter\Helpers\HiddenCarriers;
use Okay\Modules\Sviat\DeliveryFilter\Helpers\Redirector;

class BackendExtender implements ExtensionInterface
{
    public function __construct(
        private readonly DeliveryFilterHelper $helper,
        private readonly Request $request,
        private readonly Design $design,
        private readonly Redirector $redirector,
        private readonly Modules $modules,
        private readonly HiddenCarriers $hidden,
    ) {
    }

    /** ChainExtension для BackendOrdersHelper::buildFilter: статус доставки й пошук за номером відправлення. */
    public function buildFilter($filter)
    {
        if (!is_array($filter) || !$this->enabled()) {
            return $filter;
        }
        $filters = $this->hidden->visible($this->helper->sanitizedFilters());
        $raw = $this->request->get(DeliveryStatusParam::NAME);
        $selected = DeliveryStatusParam::parse($raw, $filters);
        if (($raw === null || $raw === '') && $this->request->method('get')) {
            $legacy = DeliveryStatusParam::legacy($filters, fn(string $name): mixed => $this->request->get($name));
            if ($legacy !== null) {
                $this->redirector->to($this->request->url([$legacy[0] => null, DeliveryStatusParam::NAME => $legacy[1]]));

                return $filter;
            }
        }
        $this->design->assign('delivery_status_filters', $filters);
        $this->design->assign('delivery_status', $selected === null ? '' : DeliveryStatusParam::value(...$selected));
        if ($selected !== null) {
            $filter = $this->helper->filterByDeliveryStatus($filter, ...$selected);
        }

        return $this->searchByShipment($filter);
    }

    /** ChainExtension для BackendOrdersHelper::buildCountStatusesFilter: лічильники рахують ті самі замовлення, що й список. */
    public function countFilter($countFilter, $filter = [])
    {
        if (!is_array($countFilter) || !is_array($filter) || !$this->enabled()) {
            return $countFilter;
        }
        if (isset($filter['id'])) {
            $countFilter['id'] = $filter['id'];
            if (!isset($filter['keyword'])) {
                unset($countFilter['keyword']);
            }
        }
        $selected = DeliveryStatusParam::parse($this->request->get(DeliveryStatusParam::NAME), $this->hidden->visible($this->helper->sanitizedFilters()));

        return $selected === null ? $countFilter : $this->helper->filterByDeliveryStatus($countFilter, ...$selected);
    }

    /** Адмінка запускає init() і вимкнених модулів, тож ланки вимкненого хаба зареєстровані й мусять мовчати. */
    private function enabled(): bool
    {
        return (bool)$this->modules->isActiveModule('Sviat', 'DeliveryFilter');
    }

    private function searchByShipment(array $filter): array
    {
        $keyword = $this->request->get('keyword');
        if (!is_string($keyword) || trim($keyword) === '') {
            return $filter;
        }
        $ids = array_values(array_unique(array_filter(array_map(intval(...), $this->helper->findOrderIdsByShipmentNumber($keyword)), static fn(int $id): bool => $id > 0)));
        // Не номер відправлення жодного перевізника: лишається пошук ядра за іменем, телефоном, товарами.
        if ($ids === []) {
            return $filter;
        }
        // Ядро показує текст пошуку, лише поки він лишається у фільтрі.
        $this->design->assign('keyword', $filter['keyword'] ?? $keyword);
        unset($filter['keyword']);
        if (isset($filter['id'])) {
            $ids = array_values(array_intersect($ids, array_map(intval(...), (array)$filter['id'])));
        }
        // Порожній список id означав би для фільтра ядра «без умови»: [0] не збігається ні з чим.
        $filter['id'] = $ids === [] ? [0] : $ids;

        return $filter;
    }
}

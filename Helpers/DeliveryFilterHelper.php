<?php

namespace Okay\Modules\Sviat\DeliveryFilter\Helpers;

use Okay\Core\Modules\Extender\ExtenderFacade;

/**
 * Точки розширення для модулів доставки. Перевізник додає Chain-extender на кожен метод, але реєструє його
 * лише під class_exists(DeliveryFilterHelper::class): реєстрація на неіснуючий клас кидає виняток в init()
 * і кладе весь сайт.
 */
class DeliveryFilterHelper
{
    /**
     * Групи статусів для select'а. Кожен перевізник дописує свій ключ (`np`, `up`, `meest`, `rozetka`).
     * Тексти — лише з мовних файлів модуля; група з HTML чи іншими символами поза білим списком не показується.
     *
     * @return array<string, array{title: string, short: string, options: array<string, string>, legacy_param?: string}>
     */
    public function findDeliveryStatusFilters(): array
    {
        return ExtenderFacade::execute(__METHOD__, [], func_get_args());
    }

    /** Перевізник діє лише тоді, коли `$carrier` — його ключ, і перекладає статус у свій фільтр сутності замовлень. */
    public function filterByDeliveryStatus(array $filter, string $carrier, string $status): array
    {
        return ExtenderFacade::execute(__METHOD__, $filter, func_get_args());
    }

    /**
     * Перевізник, що впізнав у тексті пошуку свій номер відправлення, дописує id замовлень. Хаб бере об'єднання.
     *
     * @return list<int>
     */
    public function findOrderIdsByShipmentNumber(string $keyword): array
    {
        return ExtenderFacade::execute(__METHOD__, [], func_get_args());
    }

    /**
     * Групи після білого списку. Навмисно не розширюється через ExtenderFacade: модуль не повинен мати змоги
     * обійти перевірку тексту, яку bootstrap-select вставляє як HTML.
     */
    public function sanitizedFilters(): array
    {
        return DeliveryStatusGroups::sanitize($this->findDeliveryStatusFilters());
    }
}

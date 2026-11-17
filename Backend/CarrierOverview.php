<?php

namespace Okay\Modules\Sviat\DeliveryFilter\Backend;

use Okay\Modules\Sviat\DeliveryFilter\Helpers\DeliveryStatusGroups;

/** Стан кожного модуля доставки щодо хаба: що він дає у фільтр списку замовлень і чому може не давати нічого. */
final class CarrierOverview
{
    public const string CONNECTED = 'connected';
    public const string DISABLED = 'disabled';
    /** Модуль увімкнено, але групи він не віддав. */
    public const string SILENT = 'silent';
    /** Група не пройшла білий список тексту, тож у фільтрі її немає. */
    public const string REJECTED = 'rejected';
    public const string ERROR = 'error';
    /** Модуль доставки без ланок на хаб. */
    public const string UNSUPPORTED = 'unsupported';

    /**
     * @param array<string, array{groups: mixed, filter: bool, search: bool}> $adapters модуль `Vendor/Name` → що повернула
     *     його власна ланка findDeliveryStatusFilters (\Throwable, якщо впала) і чи є в нього дві інші ланки
     * @param array<string, bool> $deliveryModules встановлені модулі типу «доставка» → чи ввімкнені
     * @param callable(string): bool $isActive
     * @return list<array{module: string, state: string, groups: array<string, array{title: string, short: string, options: array<string, string>}>, filter: bool, search: bool}>
     */
    public static function rows(array $adapters, array $deliveryModules, callable $isActive): array
    {
        $rows = [];
        foreach ($adapters as $module => $adapter) {
            $raw = $adapter['groups'];
            $groups = $raw instanceof \Throwable ? [] : DeliveryStatusGroups::sanitize($raw);
            $state = match (true) {
                !$isActive($module) => self::DISABLED,
                $raw instanceof \Throwable => self::ERROR,
                !is_array($raw) || $raw === [] => self::SILENT,
                count($groups) < count($raw) => self::REJECTED,
                default => self::CONNECTED,
            };
            $rows[$module] = [
                'module' => $module,
                'state' => $state,
                'groups' => $state === self::DISABLED ? [] : $groups,
                'filter' => $adapter['filter'],
                'search' => $adapter['search'],
            ];
        }
        foreach ($deliveryModules as $module => $enabled) {
            $rows[$module] ??= ['module' => $module, 'state' => self::UNSUPPORTED, 'groups' => [], 'filter' => false, 'search' => false];
        }
        ksort($rows);

        return array_values($rows);
    }

    /** `Vendor/Name` з класу модуля або null, якщо клас не з `Okay\Modules`. */
    public static function moduleOf(string $class): ?string
    {
        return preg_match('/^\\\\?Okay\\\\Modules\\\\([A-Za-z0-9_]+)\\\\([A-Za-z0-9_]+)\\\\/', $class, $m) === 1 ? $m[1] . '/' . $m[2] : null;
    }
}

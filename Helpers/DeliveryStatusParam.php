<?php

namespace Okay\Modules\Sviat\DeliveryFilter\Helpers;

/** GET-параметр `delivery_status=<перевізник>:<статус>`; приймається лише пара з описаних перевізниками груп. */
final class DeliveryStatusParam
{
    public const string NAME = 'delivery_status';
    private const string PATTERN = '/^([a-z][a-z0-9_]{0,15}):([a-z0-9_]{1,32})$/D';

    /**
     * @param array<string, array{options: array<string, string>}> $filters уже після DeliveryStatusGroups::sanitize()
     * @return ?array{string, string} [перевізник, статус]
     */
    public static function parse(mixed $raw, array $filters): ?array
    {
        if (!is_string($raw) || preg_match(self::PATTERN, $raw, $m) !== 1) {
            return null;
        }

        return isset($filters[$m[1]]['options'][$m[2]]) ? [$m[1], $m[2]] : null;
    }

    public static function value(string $carrier, string $status): string
    {
        return $carrier . ':' . $status;
    }

    /**
     * Старий параметр перевізника (`np_waybill=arrived`), якщо його значення є серед статусів групи.
     *
     * @param array<string, array{options: array<string, string>, legacy_param?: string}> $filters
     * @param callable(string): mixed $get
     * @return ?array{string, string} [назва старого параметра, канонічне значення]
     */
    public static function legacy(array $filters, callable $get): ?array
    {
        foreach ($filters as $carrier => $group) {
            $param = $group['legacy_param'] ?? null;
            if ($param === null) {
                continue;
            }
            $status = $get($param);
            if (is_string($status) && isset($group['options'][$status])) {
                return [$param, self::value($carrier, $status)];
            }
        }

        return null;
    }
}

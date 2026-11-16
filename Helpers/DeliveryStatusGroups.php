<?php

namespace Okay\Modules\Sviat\DeliveryFilter\Helpers;

/**
 * Білий список для груп перевізників. bootstrap-select 1.11.2 вставляє label optgroup як HTML уже після
 * того, як браузер декодував атрибут, тож одинарне |escape його не захищає; шаблон тримає подвійне
 * |escape|escape, а тут друга лінія: у назвах лише літери, цифри й кілька розділових знаків.
 * Група з будь-якою вадою відкидається цілком.
 */
final class DeliveryStatusGroups
{
    private const string CARRIER = '/^[a-z][a-z0-9_]{0,15}$/D';
    private const string STATUS = '/^[a-z0-9_]{1,32}$/D';
    private const string PARAM = '/^[a-z][a-z0-9_]{0,31}$/D';
    private const string TEXT = "/^[\\p{L}\\p{N} .,'’()·\\/-]{1,64}$/uD";

    /**
     * @return array<string, array{title: string, short: string, options: array<string, string>, legacy_param?: string}>
     */
    public static function sanitize(mixed $raw): array
    {
        $clean = [];
        foreach (is_array($raw) ? $raw : [] as $carrier => $group) {
            $group = self::group($carrier, $group);
            if ($group !== null) {
                $clean[$carrier] = $group;
            }
        }

        return $clean;
    }

    private static function group(mixed $carrier, mixed $group): ?array
    {
        if (!is_string($carrier) || preg_match(self::CARRIER, $carrier) !== 1 || !is_array($group)) {
            return null;
        }
        if (!self::text($group['title'] ?? null) || !self::text($group['short'] ?? null)) {
            return null;
        }
        $options = $group['options'] ?? null;
        if (!is_array($options) || $options === []) {
            return null;
        }
        foreach ($options as $status => $label) {
            // PHP робить ключ масиву з самих цифр («123») цілим числом.
            if (preg_match(self::STATUS, (string)$status) !== 1 || !self::text($label)) {
                return null;
            }
        }
        $clean = ['title' => $group['title'], 'short' => $group['short'], 'options' => $options];
        if (array_key_exists('legacy_param', $group)) {
            if (!is_string($group['legacy_param']) || preg_match(self::PARAM, $group['legacy_param']) !== 1 || $group['legacy_param'] === DeliveryStatusParam::NAME) {
                return null;
            }
            $clean['legacy_param'] = $group['legacy_param'];
        }

        return $clean;
    }

    private static function text(mixed $value): bool
    {
        return is_string($value) && preg_match(self::TEXT, $value) === 1;
    }
}

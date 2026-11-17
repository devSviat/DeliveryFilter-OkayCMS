<?php

namespace Okay\Modules\Sviat\DeliveryFilter\Helpers;

use Okay\Core\Settings;

/**
 * Перевізники, яких менеджер прибрав із select'а «Статус доставки» на сторінці модуля.
 * Пошук за номером відправлення від цього не залежить.
 */
class HiddenCarriers
{
    public const string SETTING = 'sviat__delivery_filter__hidden';
    private const string CARRIER = '/^[a-z][a-z0-9_]{0,15}$/D';

    public function __construct(private readonly Settings $settings)
    {
    }

    /** @return list<string> */
    public function all(): array
    {
        $raw = $this->settings->get(self::SETTING);
        $list = is_string($raw) ? json_decode($raw, true) : null;

        return self::clean(is_array($list) ? $list : []);
    }

    /** @param list<mixed> $carriers */
    public function save(array $carriers): void
    {
        // Рядком JSON: масив Settings зберігає через serialize(), а сток і форк читають його по-різному.
        $this->settings->set(self::SETTING, json_encode(self::clean($carriers)));
    }

    /**
     * Групи без прихованих перевізників.
     *
     * @template T
     * @param array<string, T> $filters
     * @return array<string, T>
     */
    public function visible(array $filters): array
    {
        return array_diff_key($filters, array_flip($this->all()));
    }

    /** @return list<string> */
    private static function clean(array $carriers): array
    {
        $clean = array_values(array_unique(array_filter($carriers, static fn($c): bool => is_string($c) && preg_match(self::CARRIER, $c) === 1)));
        sort($clean);

        return $clean;
    }
}

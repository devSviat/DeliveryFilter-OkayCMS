<?php

namespace Modules\Sviat\DeliveryFilter\Support;

use Okay\Core\Modules\Extender\ExtensionInterface;

/** Перевізник «aa»: статуси arrived і none, номер «AA-<id>». Стан статичний: ChainExtender створює екземпляр сам. */
class FakeCarrierA implements ExtensionInterface
{
    public static array $group = [
        'title' => 'Перевізник А',
        'short' => 'ПА',
        'options' => ['arrived' => 'Прибула', 'none' => 'Без накладної'],
        'legacy_param' => 'aa_status',
    ];
    /** @var list<int> */
    public static array $found = [];
    /** @var list<array{string, string}> */
    public static array $calls = [];

    public static function reset(): void
    {
        self::$group = [
            'title' => 'Перевізник А',
            'short' => 'ПА',
            'options' => ['arrived' => 'Прибула', 'none' => 'Без накладної'],
            'legacy_param' => 'aa_status',
        ];
        self::$found = [];
        self::$calls = [];
    }

    public function groups($filters)
    {
        $filters['aa'] = self::$group;

        return $filters;
    }

    public function filter($filter, $original = [], $carrier = '', $status = '')
    {
        self::$calls[] = [$carrier, $status];
        if ($carrier === 'aa') {
            $filter['aa_group'] = $status;
        }

        return $filter;
    }

    public function search($ids, $keyword = '')
    {
        return array_merge($ids, self::$found);
    }
}

/** Перевізник «bb» без legacy_param. */
class FakeCarrierB implements ExtensionInterface
{
    /** @var list<int> */
    public static array $found = [];

    public static function reset(): void
    {
        self::$found = [];
    }

    public function groups($filters)
    {
        $filters['bb'] = ['title' => 'Перевізник Б', 'short' => 'ПБ', 'options' => ['arrived' => 'У відділенні']];

        return $filters;
    }

    public function filter($filter, $original = [], $carrier = '', $status = '')
    {
        if ($carrier === 'bb') {
            $filter['bb_group'] = $status;
        }

        return $filter;
    }

    public function search($ids, $keyword = '')
    {
        return array_merge($ids, self::$found);
    }
}

/** Ключ перевізника з великими літерами: хаб має відкинути групу. */
class UppercaseCarrier implements ExtensionInterface
{
    public function groups($filters)
    {
        $filters['NP'] = ['title' => 'Нова Пошта', 'short' => 'НП', 'options' => ['arrived' => 'Прибула']];

        return $filters;
    }

    public function filter($filter, $original = [], $carrier = '', $status = '')
    {
        return $filter;
    }

    public function search($ids, $keyword = '')
    {
        return $ids;
    }
}

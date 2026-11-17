<?php

// Простір імен як у справжнього модуля: сторінка хаба впізнає модуль саме за ним.
namespace Okay\Modules\FakeVendor\Alpha\Extenders;

use Okay\Core\Modules\Extender\ExtensionInterface;

class HubLinks implements ExtensionInterface
{
    public function groups($filters)
    {
        $filters['al'] = ['title' => 'Альфа', 'short' => 'АЛ', 'options' => ['arrived' => 'Прибула']];

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

namespace Okay\Modules\FakeVendor\Beta\Extenders;

use Okay\Core\Modules\Extender\ExtensionInterface;

/** Ключ із великими літерами: білий список хаба групу відкидає. */
class HubLinks implements ExtensionInterface
{
    public function groups($filters)
    {
        $filters['BE'] = ['title' => 'Бета', 'short' => 'БЕ', 'options' => ['arrived' => 'Прибула']];

        return $filters;
    }
}

namespace Okay\Modules\FakeVendor\Gamma\Extenders;

use Okay\Core\Modules\Extender\ExtensionInterface;

class HubLinks implements ExtensionInterface
{
    public function groups($filters)
    {
        throw new \RuntimeException('adapter failed');
    }
}

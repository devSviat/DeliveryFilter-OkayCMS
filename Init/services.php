<?php

namespace Okay\Modules\Sviat\DeliveryFilter;

use Okay\Core\Design;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;
use Okay\Core\Modules\Modules;
use Okay\Core\Request;
use Okay\Modules\Sviat\DeliveryFilter\Extenders\BackendExtender;
use Okay\Modules\Sviat\DeliveryFilter\Helpers\DeliveryFilterHelper;
use Okay\Modules\Sviat\DeliveryFilter\Helpers\Redirector;

return [
    DeliveryFilterHelper::class => [
        'class' => DeliveryFilterHelper::class,
    ],
    Redirector::class => [
        'class' => Redirector::class,
    ],
    BackendExtender::class => [
        'class' => BackendExtender::class,
        'arguments' => [
            new SR(DeliveryFilterHelper::class),
            new SR(Request::class),
            new SR(Design::class),
            new SR(Redirector::class),
            new SR(Modules::class),
        ],
    ],
];

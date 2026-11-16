<?php

namespace Okay\Modules\Sviat\DeliveryFilter\Init;

use Okay\Admin\Helpers\BackendOrdersHelper;
use Okay\Core\Modules\AbstractInit;
use Okay\Modules\Sviat\DeliveryFilter\Extenders\BackendExtender;

class Init extends AbstractInit
{
    public function install()
    {
    }

    public function init()
    {
        $this->registerChainExtension([BackendOrdersHelper::class, 'buildFilter'], [BackendExtender::class, 'buildFilter']);
        $this->registerChainExtension([BackendOrdersHelper::class, 'buildCountStatusesFilter'], [BackendExtender::class, 'countFilter']);
    }
}

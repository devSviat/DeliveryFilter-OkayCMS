<?php

namespace Okay\Modules\Sviat\DeliveryFilter\Init;

use Okay\Admin\Helpers\BackendOrdersHelper;
use Okay\Core\Modules\AbstractInit;
use Okay\Modules\Sviat\DeliveryFilter\Extenders\BackendExtender;

class Init extends AbstractInit
{
    public function install()
    {
        $this->setBackendMainController('DeliveryFilterAdmin');
    }

    /** Хаб 1.0.0 сторінки не мав: посилання на неї зі списку модулів з'являється лише так. */
    public function update_1_1_0(): void
    {
        $this->setBackendMainController('DeliveryFilterAdmin');
    }

    public function init()
    {
        $this->registerBackendController('DeliveryFilterAdmin');
        $this->addBackendControllerPermission('DeliveryFilterAdmin', 'delivery');
        $this->registerChainExtension([BackendOrdersHelper::class, 'buildFilter'], [BackendExtender::class, 'buildFilter']);
        $this->registerChainExtension([BackendOrdersHelper::class, 'buildCountStatusesFilter'], [BackendExtender::class, 'countFilter']);
    }
}

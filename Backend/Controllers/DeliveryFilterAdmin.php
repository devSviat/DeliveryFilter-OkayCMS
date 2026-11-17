<?php

namespace Okay\Modules\Sviat\DeliveryFilter\Backend\Controllers;

use Okay\Admin\Controllers\IndexAdmin;
use Okay\Modules\Sviat\DeliveryFilter\Backend\HubAdapters;
use Okay\Modules\Sviat\DeliveryFilter\Helpers\HiddenCarriers;

/** Сторінка хаба: стан під'єднаних модулів доставки й вибір, чиї групи показувати в select'і. */
class DeliveryFilterAdmin extends IndexAdmin
{
    public function fetch(HubAdapters $adapters, HiddenCarriers $hidden): void
    {
        if ($this->request->method('post')) {
            // Сток 4.5.3 перевіряє session_id лише після виконання контролера, тож підроблений POST уже встиг би спрацювати.
            if (!$this->request->checkSession()) {
                $this->response->setStatusCode(403);
                $this->response->setContent('Session expired', RESPONSE_TEXT);

                return;
            }
            $hidden->save(self::hiddenFromPost($this->request->post('carriers'), $this->request->post('visible')));
            $this->design->assign('message_success', 'saved');
        }

        $carriers = $adapters->overview();
        $this->design->assign('delivery_filter_carriers', $carriers);
        $this->design->assign('delivery_filter_hidden', array_fill_keys($hidden->all(), true));
        $this->design->assign('delivery_filter_toggles', array_filter(array_column($carriers, 'groups')) !== []);
        $this->response->setContent($this->design->fetch('delivery_filter.tpl'));
    }

    /**
     * Форма надсилає ключі всіх перемикачів (`carriers[]`) і ключі ввімкнених (`visible[]`): невибраний
     * checkbox браузер не надсилає зовсім.
     *
     * @return list<mixed>
     */
    public static function hiddenFromPost(mixed $carriers, mixed $visible): array
    {
        $strings = static fn(mixed $list): array => array_values(array_filter(is_array($list) ? $list : [], is_string(...)));

        return array_values(array_diff($strings($carriers), $strings($visible)));
    }
}

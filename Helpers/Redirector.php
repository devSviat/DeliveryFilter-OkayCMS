<?php

namespace Okay\Modules\Sviat\DeliveryFilter\Helpers;

use Okay\Core\Response;

/** Окремий клас, бо Response::redirectTo завершує запит, а тестам потрібна адреса. */
class Redirector
{
    public function to(string $url): void
    {
        Response::redirectTo($url);
    }
}

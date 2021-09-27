<?php
/**
 * Created by PhpStorm.
 * User: BCT
 * Date: 27/09/2021
 * Time: 14:18
 */

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class RequestListener
{
    public function onKernelRequest(RequestEvent $event)
    {
        // The isMainRequest() method was introduced in Symfony 5.3.
        // In previous versions it was called isMasterRequest()
        if (!$event->isMainRequest()) {
            // don't do anything if it's not the main request
            return;
        }

        // ...
    }
}
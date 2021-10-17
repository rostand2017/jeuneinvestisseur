<?php
/**
 * Created by PhpStorm.
 * User: BCT
 * Date: 27/09/2021
 * Time: 14:18
 */

namespace App\EventListener;

use App\Controller\DefaultController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RequestListener
{
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if ($event->isMainRequest()) {
            // $data = $this->getBtcCurrency();
            // $event->getRequest()->getSession()->set("btc", $data);
        }

    }
}
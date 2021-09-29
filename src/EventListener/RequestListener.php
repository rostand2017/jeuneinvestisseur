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
            $data = $this->getBtcCurrency();
            $event->getRequest()->getSession()->set("btc", $data);
        }

    }

    private function getBtcCurrency(){
        $responseHttp = $this->client->request(
            'GET',
            'https://pro-api.coinmarketcap.com/v1/cryptocurrency/listings/latest',
            ['query'=>['limit' => 1], "headers"=>['X-CMC_PRO_API_KEY'=>DefaultController::COINMARKET_APIKEY]]
        );
        $datas = $responseHttp->toArray()['data'];
        $datas[0]['logo'] = $this->getCrypoLogo($datas[0]['symbol']);
        return $datas[0];
    }

    private function getCrypoLogo($symbol): string {
        $responseHttp = $this->client->request(
            'GET',
            'https://pro-api.coinmarketcap.com/v1/cryptocurrency/info',
            ["query"=>["symbol"=>$symbol], "headers"=>['X-CMC_PRO_API_KEY'=>DefaultController::COINMARKET_APIKEY]]
        );
        return $responseHttp->toArray()['data'][$symbol]['logo'];
    }
}
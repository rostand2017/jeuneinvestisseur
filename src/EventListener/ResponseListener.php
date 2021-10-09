<?php
/**
 * Created by PhpStorm.
 * User: BCT
 * Date: 27/09/2021
 * Time: 14:58
 */

namespace App\EventListener;


use App\Entity\Visitor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ResponseListener
{
    private $client;
    private $em;
    public function __construct(EntityManagerInterface $em, HttpClientInterface $client)
    {
        $this->client = $client;
        $this->em = $em;
    }

    public function onKernelResponse(ResponseEvent $responseEvent){
        //$this->setRegionAndCoor($responseEvent);
        $response = $responseEvent->getResponse();
        $visitorKey = $responseEvent->getRequest()->cookies->get("rx-visitor", false);
        if(!$visitorKey){
            $ip = $responseEvent->getRequest()->getClientIp();
            $visitors = $this->em->getRepository(Visitor::class)->findByIp($ip);
            if(count($visitors) > 0){
                $date = new \DateTime();
                if($date->format("d") == $visitors[0]->getCreatedat()->format("d") &&
                    $date->format("m") == $visitors[0]->getCreatedat()->format("m") &&
                    $date->format("y") == $visitors[0]->getCreatedat()->format("y")){
                    return;
                }
            }

            $responseHttp = $this->client->request(
                'GET',
                'http://ip-api.com/json/'.$ip,
                ['json' => true]
            );
            $visitor = new Visitor();
            $visitorKey = md5(uniqid());
            $visitor->setIp($ip);
            $visitor->setViewerkey($visitorKey);
            $visitor->setCountry($responseHttp->toArray()['country']);
            //$visitor->setCountry("Cameroun");
            $this->em->persist($visitor);
            $this->em->flush();
            $response->headers->setCookie(Cookie::create("rx-visitor", $visitorKey, time() + 86400));
        }
    }

    private function setRegionAndCoor(ResponseEvent $responseEvent){
        if(!$responseEvent->getRequest()->getSession()->get('rx-coordinate', false)) {
            $ip = $responseEvent->getRequest()->getClientIp();
            $responseHttp = $this->client->request(
                'GET',
                'http://ip-api.com/json/' . $ip,
                ['json' => true]
            );
            $coordinate = ['city' => $responseHttp->toArray()['city'], 'lat' => $responseHttp->toArray()['lat'], 'lon' => $responseHttp->toArray()['lon']];
            $responseEvent->getRequest()->getSession()->set("rx-coordinate", $coordinate);
        }
    }
}
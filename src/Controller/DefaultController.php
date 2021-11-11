<?php
/**
 * Created by PhpStorm.
 * User: Ross
 * Date: 2/16/2020
 * Time: 8:58 PM
 */

namespace App\Controller;


use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Emails;
use App\Entity\News;
use App\Entity\User;
use App\Entity\Viewers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DefaultController extends AbstractController
{
    /**
     * get most popular news (5)
     * get news per category (4 news per category)
     * get the 4 last news
     * @return \Symfony\Component\HttpFoundation\Response
     */
    const NEWS_SESSIONS = "news_sessions";
    const COINMARKET_APIKEY = "a3a63f25-480f-404d-8ee1-357a043f5e18";
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function index(){

        $em = $this->getDoctrine()->getManager();
        $lastFournews = $em->getRepository(News::class)->findBy(['isDeleted'=>0], ['createdat'=>'desc'], 3, 0);
        $popularsNews = $em->getRepository(News::class)->getPopularsNews(5);
        $categories = $em->getRepository(Category::class)->findAll();
        $categoriesWithNews = [];
        foreach ($categories as $category){
            $news = $em->getRepository(News::class)->findBy(['isDeleted'=>0, 'category'=>$category->getId()], ['createdat'=>'desc'], 4, 0);
            array_push($categoriesWithNews, ['category'=>$category, 'news'=>$news]);
        }
        return $this->render('user_news/index.html.twig', compact("categories", "lastFournews", "categoriesWithNews", "popularsNews"));
    }

    public function news(){
        return $this->render('user_news/news.html.twig', array());
    }

    public function newsCategory(Request $request, Category $category, string $title){
        $em = $this->getDoctrine()->getManager();
        $search = $request->query->get('q');
        $position = $request->query->get('p');
        $position = $position ? $position : 0;
        $search = $search ? $search : "";
        $news = $em->getRepository(News::class)->getNewsByTitleContent($category, $search, $position);
        $popularsNews = $em->getRepository(News::class)->getPopularsNews(5);
        $categories = $em->getRepository(Category::class)->findAll();
        $categoriesWithNews = [];
        foreach ($categories as $_category){
            $_news = $em->getRepository(News::class)->findBy(['isDeleted'=>0, 'category'=>$category->getId()], ['createdat'=>'desc'], 4, 0);
            array_push($categoriesWithNews, ['category'=>$_category, 'news'=>$_news]);
        }
        return $this->render('user_news/news_category.html.twig', compact("categories", "categoriesWithNews", "news", "category", "title", "popularsNews"));
    }

    public function details(Request $request, News $news, HttpClientInterface $client){
        $newsSession = $request->getSession()->get(self::NEWS_SESSIONS, false);
        $em = $this->getDoctrine()->getManager();
        $popularsNews = $em->getRepository(News::class)->getPopularsNews(5);

        if(!$newsSession || !array_key_exists("news-".$news->getId(), $newsSession)){
            $viewer = new Viewers();
            $key = $this->getUniqueKey();
            $ip = $request->getClientIp();

            $responseHttp = $client->request(
                'GET',
                'http://ip-api.com/json/'.$ip,
                ['json' => true]
            );
            $news->setViews($news->getViews()+1);
            $viewer->setNews($news)
                ->setViewerkey($key)
                ->setIp($ip)
                //->setCountry($responseHttp->toArray()['country']);
                ->setCountry("Cameroun");
            $newsSession["news-".$news->getId()] = ["newsId"=>$news->getId(), "viewKey"=>$key];
            $request->getSession()->set(self::NEWS_SESSIONS, $newsSession);
            $em->persist($viewer);
            $em->flush();
        }
        $categories = $em->getRepository(Category::class)->findAll();
        $viewers = $em->getRepository(Viewers::class)->findByNews($news->getId());
        $comments = $em->getRepository(Comment::class)->findByNews($news->getId());
        $categoriesWithNews = [];
        foreach ($categories as $category){
            $_news = $em->getRepository(News::class)->findBy(['isDeleted'=>0, 'category'=>$category->getId()], ['createdat'=>'desc'], 4, 0);
            array_push($categoriesWithNews, ['category'=>$category, 'news'=>$_news]);
        }
        $tags = mb_split(",", $news->getTags());
        return $this->render('user_news/news_detail.html.twig', compact("categoriesWithNews", "categories", "news", "viewers", "comments", "popularsNews", "tags"));
    }

    public function saveComment(Request $request, News $news){
        $em = $this->getDoctrine()->getManager();
        $message = $request->request->get('message');
        $name = $request->request->get('name');
        $email = $request->request->get('email');
        $parentComment = $request->request->get('comment');
        if( !$message || $message == ''){
            return new JsonResponse(['status'=>0, 'message'=> 'Du musst eine Message hinzufügen']);
        }
        $comment = new Comment();
        if($parentComment && trim($parentComment) != ""){
            $p = $em->getRepository(Comment::class)->find($parentComment);
            $comment->setComment($p);
        }
        $comment->setContent($message);
        $comment->setNews($news);
        if($this->getUser()){
            $comment->setUser($this->getUser());
            $comment->setName($this->getUser()->getName());
            $comment->setEmail($this->getUser()->getEmail());
        }else{
            if($email && $name && preg_match("#.+@[a-zA-Z]+\.[a-zA-Z]{2,6}#", $email)){
                $comment->setEmail($email);
                $comment->setName($name);
            }else{
                return new JsonResponse(['status'=>0, 'message'=> 'Du musst eine E-mail und einen Name hinzufügen']);
            }
        }
        $em->persist($comment);
        $em->flush();
        return new JsonResponse(['status'=>1, 'message'=> 'Gut']);
    }

    public function subscribe(Request $request){
        $email = $request->request->get('email');
        if($email && preg_match("#.+@[a-zA-Z]+\.[a-zA-Z]{2,6}#", $email) || preg_match("#[0-9\+]{8,14}#", $email)){
            $em = $this->getDoctrine()->getManager();
            $emails = $em->getRepository(Emails::class)->findOneByEmail($email);
            if($emails)
                return new JsonResponse(['status'=>0, 'message'=> 'Vous êtes déjà abonné']);
            $e = new Emails();
            $e->setEmail($email);
            $em->persist($e);
            $em->flush();
            return new JsonResponse(['status'=>1, 'message'=> 'Abonnement réussi']);

        }else{
            return new JsonResponse(['status'=>0, 'message'=> 'Renseignez une adresse email ou un numéro valable']);
        }
    }

    public function incrementReadDuration(Request $request, $viewsKey){
        $em = $this->getDoctrine()->getManager();
        $viewers = $em->getRepository(Viewers::class)->findOneByViewerkey($viewsKey);
        $newsSession = $request->getSession()->get(self::NEWS_SESSIONS, false);
        $news = $viewers->getNews();
        if($newsSession && $viewers->getViewerkey() == $newsSession["news-".$news->getId()]["viewKey"] && $news->getReadDuration() != 0 && $viewers->getDuration() < $news->getReadDuration()){
            $em = $this->getDoctrine()->getManager();
            $viewers->setDuration($viewers->getDuration()+1);
            $viewers->setReadPercentage($viewers->getDuration()/$news->getReadDuration()*100);
            $news->setDurationTotal($news->getDurationTotal()+1);
            $em->persist($news);
            $em->persist($viewers);
            $em->flush();
            return new JsonResponse(['status'=>1]);
        }else{
            return new JsonResponse(['status'=>0, "ss"=>'  '.$viewers->getViewerkey()]);
        }
    }

    public function getUniqueKey(){
        return md5(uniqid());
    }

    public function getCryptoCurrencyList(){
        $responseHttp = $this->client->request(
            'GET',
            'https://pro-api.coinmarketcap.com/v1/cryptocurrency/listings/latest',
            ['query'=>['limit' => 10, "sort"=>"market_cap"], "headers"=>['X-CMC_PRO_API_KEY'=>self::COINMARKET_APIKEY]]
        );
        $datas = $responseHttp->toArray()['data'];
        $i = 0;
        foreach ($datas as $data){
            $datas[$i]['logo'] = $this->getCrypoLogo($data['symbol']);
            $i++;
        }
        return new JsonResponse($datas);
    }

    public function getBtcCurrency(){
        $responseHttp = $this->client->request(
            'GET',
            'https://pro-api.coinmarketcap.com/v1/cryptocurrency/listings/latest',
            ['query'=>['limit' => 1], "headers"=>['X-CMC_PRO_API_KEY'=>DefaultController::COINMARKET_APIKEY]]
        );
        $datas = $responseHttp->toArray()['data'];
        $datas[0]['logo'] = $this->getCrypoLogo($datas[0]['symbol']);
        return new JsonResponse($datas[0]);
    }

    private function getCrypoLogo($symbol): string {
        $responseHttp = $this->client->request(
            'GET',
            'https://pro-api.coinmarketcap.com/v1/cryptocurrency/info',
            ["query"=>["symbol"=>$symbol], "headers"=>['X-CMC_PRO_API_KEY'=>self::COINMARKET_APIKEY]]
        );
        return $responseHttp->toArray()['data'][$symbol]['logo'];
    }
}
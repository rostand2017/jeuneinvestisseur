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

    public function index(){
        $em = $this->getDoctrine()->getManager();
        $lastFournews = $em->getRepository(News::class)->findBy([], ['createdat'=>'desc'], 3, 0);
        $popularsNews = $em->getRepository(News::class)->getPopularsNews(5);
        $categories = $em->getRepository(Category::class)->findAll();
        $categoriesWithNews = [];
        foreach ($categories as $category){
            $news = $em->getRepository(News::class)->findBy(['category'=>$category->getId()], ['createdat'=>'desc'], 4, 0);
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
            $_news = $em->getRepository(News::class)->findBy(['category'=>$_category->getId()], ['createdat'=>'desc'], 4, 0);
            array_push($categoriesWithNews, ['category'=>$_category, 'news'=>$_news]);
        }
        return $this->render('user_news/news_category.html.twig', compact("categories", "categoriesWithNews", "news", "category", "title", "popularsNews"));
    }

    public function details(Request $request, News $news, HttpClientInterface $client){
        $newsSession = $request->getSession()->get(self::NEWS_SESSIONS, false);
        $em = $this->getDoctrine()->getManager();
        $popularsNews = $em->getRepository(News::class)->getPopularsNews(5);
        if($newsSession){
            if(!array_key_exists($news->getId(), $newsSession)){
                $viewer = new Viewers();
                $key = $this->getUniqueKey();
                $ip = $request->getClientIp();
                // to remove on production
                $ip = "150.72.150.181";

                $responseHttp = $client->request(
                    'GET',
                    'http://ip-api.com/json/'.$ip,
                    ['json' => true]
                );
                $news->setViews($news->getViews()+1);
                $viewer->setNews($news)
                    ->setViewerkey($key)
                    ->setIp($ip)
                    ->setCountry($responseHttp->toArray()['country']);
                array_push($newsSession, $news->getId());
                $request->getSession()->set(self::NEWS_SESSIONS, $newsSession);
                $em->persist($viewer);
                $em->flush();
            }
        }else{
            $request->getSession()->set(self::NEWS_SESSIONS, [$news->getId()]);
        }
        $categories = $em->getRepository(Category::class)->findAll();
        $viewers = $em->getRepository(Viewers::class)->findByNews($news->getId());
        $comments = $em->getRepository(Comment::class)->findByNews($news->getId());
        $categoriesWithNews = [];
        foreach ($categories as $category){
            $_news = $em->getRepository(News::class)->findBy(['category'=>$category->getId()], ['createdat'=>'desc'], 4, 0);
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
        if($email && preg_match("#.+@[a-zA-Z]+\.[a-zA-Z]{2,6}#", $email)){
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
            return new JsonResponse(['status'=>0, 'message'=> 'Renseignez une adresse email valable']);
        }
    }

    public function getUniqueKey(){
        return md5(uniqid());
    }
}
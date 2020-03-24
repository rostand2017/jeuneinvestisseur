<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method News|null find($id, $lockMode = null, $lockVersion = null)
 * @method News|null findOneBy(array $criteria, array $orderBy = null)
 * @method News[]    findAll()
 * @method News[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, News::class);
    }

    public function getNewsByTitleContent(Category $category, string $search, int $offset)
    {
        $qb = $this->createQueryBuilder('n');
         $result = $qb->where($qb->expr()->orX(
                $qb->expr()->like('n.content', ':content'),
                $qb->expr()->like('n.title', ':title')
            ))
             ->andWhere("n.category = :category")
             ->orderBy('n.updatedat', 'desc')
            ->setParameter('title', "%".$search."%")
            ->setParameter('content', "%".$search."%")
            ->setParameter('category', $category->getId())
            ->getQuery()->setFirstResult($offset)->setMaxResults($offset + 20)
            ->getResult();
        return $result;
    }


    public function getNewsByTitleContent2(string $search, int $offset)
    {
        $qb = $this->createQueryBuilder('n');
         $result = $qb->where($qb->expr()->orX(
                $qb->expr()->like('n.content', ':content'),
                $qb->expr()->like('n.title', ':title')
            ))
             ->orderBy('n.updatedat', 'desc')
            ->setParameter('title', "%".$search."%")
            ->setParameter('content', "%".$search."%")
            ->getQuery()->setFirstResult($offset)->setMaxResults($offset + 3)
            ->getResult();
        return $result;
    }

    public function getPopularsNews(int $nb){
        $connection = $this->_em->getConnection();
        $sql = "SELECT n.id, n.title, n.content, n.image, n.createdat, COUNT(*) AS `nb_viewers` FROM news n LEFT JOIN viewers v ON v.news = n.id WHERE YEAR(n.createdat) = (SELECT MAX(YEAR(createdat)) FROM news) GROUP BY n.id ORDER BY `nb_viewers` DESC LIMIT 0,".$nb;
        $statement = $connection->prepare($sql);
        $statement->execute();
        return $statement->fetchAll();
    }
}

<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 *
 * @method Review|null find($id, $lockMode = null, $lockVersion = null)
 * @method Review|null findOneBy(array $criteria, array $orderBy = null)
 * @method Review[]    findAll()
 * @method Review[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Review $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Review $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return Review[] Returns an array of Review objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    public function findByUserAndEventIds($order = 'DESC', $userId, $setlistId)
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.user', 'u')
            ->innerJoin('r.event', 'e')
            ->addSelect('u')
            ->addSelect('e')
            ->andWhere('u.id = :uVal')
            ->andWhere('e.setlistId = :eVal')
            ->setParameter('uVal', $userId)
            ->setParameter('eVal', $setlistId)
            ->orderBy('r.createdAt', $order)
            ->getQuery()
            ->getResult();
    }

    public function findByEvent($order, $setlistId)
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.event', 'e')
            ->addSelect('e')
            ->andWhere('e.setlistId = :val')
            ->setParameter('val', $setlistId)
            ->orderBy('r.createdAt', $order)
            ->getQuery()
            ->getResult();
    }

    public function findByUser($order, $userId)
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.user', 'u')
            ->addSelect('u')
            ->andWhere('u.id = :val')
            ->setParameter('val', $userId)
            ->orderBy('r.createdAt', $order)
            ->getQuery()
            ->getResult();
    }

    public function findByLatest($order = "DESC", $limit)
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.createdAt', $order)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    public function findByUserAndEvent($user, $event)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.event = :event')
            ->setParameter('user', $user)
            ->setParameter('event', $event)
            ->getQuery()
            ->getResult();
    }
}

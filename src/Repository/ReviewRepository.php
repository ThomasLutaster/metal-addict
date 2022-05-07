<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Event;
use App\Entity\Review;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

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

    /**
     * Find the reviews for a specific user and a specific event using ids
     */
    public function findByUserAndEventIds(string $order = 'DESC', int $userId, string $setlistId): ?array
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

    /**
     * Find the reviews for a specific event
     */
    public function findByEvent(string $order, string $setlistId): ?array
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

    /**
     * Find the reviews for a specific user
     */
    public function findByUser(string $order, int $userId): ?array
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

    /**
     * Find the reviews for a number of determined results
     */
    public function findByLatest(string $order = "DESC", int $limit): ?array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.createdAt', $order)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find the pictures for a specific user and a specific event using user and event objects
     */
    public function findByUserAndEvent(User $user, Event $event): ?array
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

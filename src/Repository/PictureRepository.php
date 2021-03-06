<?php

namespace App\Repository;

use App\Entity\Picture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Picture>
 *
 * @method Picture|null find($id, $lockMode = null, $lockVersion = null)
 * @method Picture|null findOneBy(array $criteria, array $orderBy = null)
 * @method Picture[]    findAll()
 * @method Picture[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PictureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Picture::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Picture $entity, bool $flush = true): void
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
    public function remove(Picture $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Find the pictures for a specific user and a specific event
     */
    public function findByUserAndEvent(int $user, string $event, string $order): ?array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.user', 'u')
            ->innerJoin('p.event', 'e')
            ->andWhere('u.id = :user')
            ->andWhere('e.setlistId = :event')
            ->setParameter('user', $user)
            ->setParameter('event', $event)
            ->orderBy('p.createdAt', $order)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find the pictures for a specific user
     */
    public function findByUser(int $user, string $order): ?array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.user', 'u')
            ->andWhere('u.id = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', $order)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find the pictures for a specific event
     */
    public function findByEvent(string $event, string $order): ?array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.event', 'e')
            ->andWhere('e.setlistId = :event')
            ->setParameter('event', $event)
            ->orderBy('p.createdAt', $order)
            ->getQuery()
            ->getResult();
    }
}

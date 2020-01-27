<?php

namespace App\Repository;

use App\Entity\Point;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Point|null find($id, $lockMode = null, $lockVersion = null)
 * @method Point|null findOneBy(array $criteria, array $orderBy = null)
 * @method Point[]    findAll()
 * @method Point[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Point::class);
    }

    public function findPointsCity($City, $offset, $limit): array
    {
        $createQueryBuilder = $this->getEntityManager()->createQueryBuilder();

        $createQueryBuilder
            ->select        ('p')
            ->from          ($this->getClassName(), 'p')
            ->where         ('p.city = :city')
            ->setParameter  ('city', $City)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults ($limit)
            ->setFirstResult($offset)
        ;
        return $createQueryBuilder->getQuery()->getArrayResult();

    }
}

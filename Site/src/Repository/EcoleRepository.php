<?php

namespace App\Repository;

use App\Entity\Ecole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ecole>
 *
 * @method Ecole|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ecole|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ecole[]    findAll()
 * @method Ecole[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EcoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ecole::class);
    }

    public function save(Ecole $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Ecole $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve toutes les écoles validées
     */
    public function findValides(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.valide = :valide')
            ->setParameter('valide', true)
            ->orderBy('e.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les écoles en attente de validation
     */
    public function findEnAttente(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.valide = :valide')
            ->setParameter('valide', false)
            ->orderBy('e.dateCreation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les écoles proposées par un utilisateur
     */
    public function findByProposeurId(int $proposeurId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.proposeurId = :proposeurId')
            ->setParameter('proposeurId', $proposeurId)
            ->orderBy('e.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche d'écoles par nom ou ville
     */
    public function search(string $term): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.valide = :valide')
            ->andWhere('e.nom LIKE :term OR e.ville LIKE :term')
            ->setParameter('valide', true)
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('e.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

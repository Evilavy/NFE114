<?php

namespace App\Repository;

use App\Entity\Enfant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Enfant>
 *
 * @method Enfant|null find($id, $lockMode = null, $lockVersion = null)
 * @method Enfant|null findOneBy(array $criteria, array $orderBy = null)
 * @method Enfant[]    findAll()
 * @method Enfant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EnfantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enfant::class);
    }

    public function save(Enfant $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Enfant $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve tous les enfants d'un utilisateur
     */
    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('e.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les enfants validés d'un utilisateur
     */
    public function findValidesByUserId(int $userId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.userId = :userId')
            ->andWhere('e.valide = :valide')
            ->setParameter('userId', $userId)
            ->setParameter('valide', true)
            ->orderBy('e.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les enfants en attente de validation
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
     * Vérifie si un enfant est dans des trajets actifs
     */
    public function isEnfantDansTrajetsActifs(int $enfantId): bool
    {
        // Cette méthode devra être adaptée selon votre entité Trajet
        // Pour l'instant, on retourne false
        return false;
    }
}

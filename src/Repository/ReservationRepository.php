<?php
// src/Repository/ReservationRepository.php
namespace App\Repository;

use App\Entity\Materiel;
use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Trouve la réservation avec la date de fin la plus récente pour un matériel donné.
     * @param Materiel $materiel
     * @return Reservation|null
     */
    public function findDerniereReservationPourMateriel(Materiel $materiel): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.materiel = :materiel')
            ->setParameter('materiel', $materiel)
            ->orderBy('r.dateFin', 'DESC') // Trie par date de fin décroissante
            ->setMaxResults(1) // Ne prend que la première (la plus récente)
            ->getQuery()
            ->getOneOrNullResult() // Retourne la réservation ou null s'il n'y en a pas
        ;
    }

    // ... autres méthodes du repository ...
}
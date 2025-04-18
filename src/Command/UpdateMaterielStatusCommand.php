<?php
// src/Command/UpdateMaterielStatusCommand.php
namespace App\Command;

use App\Entity\Materiel;
use App\Repository\MaterielRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-materiel-status',
    description: 'Vérifie les matériels loués dont la dernière réservation est terminée et les passe à "libre".',
)]
class UpdateMaterielStatusCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private MaterielRepository $materielRepository;
    private ReservationRepository $reservationRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        MaterielRepository $materielRepository,
        ReservationRepository $reservationRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->materielRepository = $materielRepository;
        $this->reservationRepository = $reservationRepository;
    }

    protected function configure(): void
    {
        // On pourrait ajouter des options/arguments ici si besoin
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTime(); // Date/heure actuelle
        $materielsMisAJour = 0;

        $io->writeln(sprintf('Début de la vérification des statuts de matériel (%s)...', $now->format('Y-m-d H:i:s')));

        // 1. Récupérer tous les matériels actuellement marqués comme 'loue'
        $materielsLoues = $this->materielRepository->findBy(['etat' => Materiel::ETAT_LOUE]);

        if (empty($materielsLoues)) {
            $io->success('Aucun matériel n\'est actuellement à l\'état "loue". Terminé.');
            return Command::SUCCESS;
        }

        $io->writeln(sprintf('Vérification de %d matériel(s) à l\'état "loue".', count($materielsLoues)));

        foreach ($materielsLoues as $materiel) {
            // 2. Pour chaque matériel loué, trouver sa dernière réservation (celle avec la date de fin la plus grande)
            $derniereReservation = $this->reservationRepository->findDerniereReservationPourMateriel($materiel);

            if (!$derniereReservation) {
                // Cas étrange: matériel 'loue' mais pas de réservation ? On le remet libre par sécurité.
                $io->warning(sprintf('Matériel ID %d est "loue" mais n\'a pas de réservation associée. Passage à "libre".', $materiel->getId()));
                $materiel->setEtat(Materiel::ETAT_LIBRE);
                $this->entityManager->persist($materiel);
                $materielsMisAJour++;
                continue; // Passe au matériel suivant
            }

            // 3. Vérifier si la date de fin de cette dernière réservation est dépassée
            if ($derniereReservation->getDateFin() < $now) {
                $io->writeln(sprintf(
                    '-> Dernière réservation (ID %d) pour matériel ID %d terminée le %s. Passage à "libre".',
                    $derniereReservation->getId(),
                    $materiel->getId(),
                    $derniereReservation->getDateFin()->format('Y-m-d H:i:s')
                ));
                $materiel->setEtat(Materiel::ETAT_LIBRE);
                $this->entityManager->persist($materiel);
                $materielsMisAJour++;
            } else {
                 $io->writeln(sprintf('-> Matériel ID %d toujours en cours de location jusqu\'au %s.', $materiel->getId(), $derniereReservation->getDateFin()->format('Y-m-d H:i:s')));
            }
        }

        // 4. Sauvegarder tous les changements en une fois
        if ($materielsMisAJour > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('%d matériel(s) ont été mis à jour à l\'état "libre".', $materielsMisAJour));
        } else {
            $io->success('Aucun statut de matériel n\'a nécessité de mise à jour.');
        }

        return Command::SUCCESS;
    }
}
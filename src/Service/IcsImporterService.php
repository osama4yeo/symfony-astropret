<?php

namespace App\Service;

// --- Imports des classes nécessaires ---
use App\Entity\Event; // L'entité Doctrine qui représente un événement dans notre base de données.
use Doctrine\ORM\EntityManagerInterface; // Le service de Doctrine pour interagir avec la base de données (sauvegarder, etc.).
use ICal\ICal; // La bibliothèque externe (PHP-iCalendar) utilisée pour parser les fichiers .ics.
use Psr\Log\LoggerInterface; // L'interface de logging de Symfony pour enregistrer des messages.

/**
 * Service chargé d'importer des événements depuis une URL de calendrier au format ICS.
 * Il gère la lecture du fichier, la conversion des données, et évite les doublons.
 */
class IcsImporterService
{
    private EntityManagerInterface $em; // Propriété pour stocker le gestionnaire d'entités.
    private LoggerInterface $logger;   // Propriété pour stocker le service de logging.

    /**
     * Le constructeur est appelé par le conteneur de services de Symfony.
     * Il injecte les dépendances nécessaires (EntityManager et Logger).
     *
     * @param EntityManagerInterface $em Le service pour gérer la persistance des données.
     * @param LoggerInterface $logger Le service pour enregistrer des logs.
     */
    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * Importe et persiste les événements depuis une URL de fichier .ics.
     *
     * @param string $url L'URL du calendrier à importer.
     * @return array Un tableau résumant le résultat de l'importation (événements ajoutés, ignorés, message d'erreur).
     */
    public function importFromUrl(string $url): array
    {
        // --- PRÉ-TRAITEMENT DE L'URL ---
        // Les clients de calendrier (comme sur iOS) utilisent souvent le préfixe 'webcal://'.
        // Ce n'est pas un vrai protocole HTTP, donc on le remplace par 'http://' pour que
        // la bibliothèque puisse télécharger le fichier correctement.
        $url = str_replace('webcal://', 'http://', $url);

        // On définit un fuseau horaire par défaut au cas où les événements dans le fichier .ics n'en spécifieraient pas.
        $defaultTimezoneString = 'Europe/Paris';

        try {
            // Options pour la bibliothèque ICal.
            $opts = [
                'http_opts' => [
                    // Cette option désactive la vérification du certificat SSL.
                    // C'est une solution de contournement pour les serveurs qui ont un certificat auto-signé ou invalide.
                    // ATTENTION: Cela réduit la sécurité de la connexion.
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                ],
            ];
            // On instancie la bibliothèque ICal en lui passant l'URL et les options.
            // La bibliothèque va tenter de télécharger et de parser le contenu du calendrier.
            $ics = new ICal($url, array_merge([
                'defaultTimeZone' => $defaultTimezoneString, // Fuseau horaire par défaut.
                'skipRecurrence'  => false, // On demande de ne PAS ignorer les événements récurrents.
            ], $opts));
        } catch (\Exception $e) {
            // Si la lecture de l'URL ou le parsing échoue, on capture l'exception.
            $this->logger->error('Erreur lors de l\'initialisation ou de la lecture de l\'URL ICS: ' . $url, ['exception' => $e]);
            // On retourne un message d'erreur clair à l'appelant (le contrôleur).
            return ['error' => 'Impossible de lire le calendrier depuis l\'URL fournie. Détail : ' . $e->getMessage()];
        }

        // Si le calendrier est vide, on arrête le processus ici.
        if (!$ics->hasEvents()) {
            return ['added' => 0, 'skipped' => 0, 'message' => 'Aucun événement trouvé dans le calendrier.'];
        }
        
        // On récupère tous les événements parsés par la bibliothèque.
        $events = $ics->events();
        
        // --- LOGIQUE DE DÉDOUBLONNAGE (TRÈS IMPORTANT) ---
        // 1. On extrait les identifiants uniques (UID) de TOUS les événements du fichier .ics.
        // L'UID est la clé qui permet d'identifier un événement de manière unique à travers les synchronisations.
        $allUidsFromIcs = array_map(fn($e) => $e->uid, $events);
        if (empty($allUidsFromIcs)) {
             return ['added' => 0, 'skipped' => 0, 'message' => 'Aucun événement avec un UID trouvé.'];
        }

        // 2. On fait UNE SEULE requête à la base de données pour récupérer tous les événements
        // qui existent déjà et dont l'UID est dans la liste des UID qu'on vient d'importer.
        $existingEvents = $this->em->getRepository(Event::class)->findBy(['uid' => $allUidsFromIcs]);
        
        // 3. On transforme le résultat en un tableau de recherche rapide (lookup table) pour une performance optimale.
        // Au lieu de chercher dans un tableau d'objets à chaque tour de boucle, on utilisera `isset()`, ce qui est quasi-instantané.
        $existingUids = [];
        foreach ($existingEvents as $existingEvent) {
            $existingUids[$existingEvent->getUid()] = true; // La clé est l'UID, la valeur n'a pas d'importance.
        }
        
        // Initialisation des compteurs pour le rapport final.
        $added = 0;
        $skipped = 0;

        // Préparation de la liste des fuseaux horaires valides et du fuseau par défaut.
        $validTimezones = \DateTimeZone::listIdentifiers();
        $defaultTimezone = new \DateTimeZone($defaultTimezoneString);

        // --- TRAITEMENT ET PERSISTANCE DES ÉVÉNEMENTS ---
        foreach ($events as $icsEvent) {
            // On vérifie si l'UID de l'événement actuel existe déjà dans notre base de données.
            if (isset($existingUids[$icsEvent->uid])) {
                $skipped++; // Si oui, on l'ignore et on incrémente le compteur des "skipped".
                continue; // On passe à l'itération suivante de la boucle.
            }

            // Si l'événement est nouveau, on crée une nouvelle entité Doctrine.
            $event = new Event();
            $event->setUid($icsEvent->uid); // On stocke l'UID pour les futures synchronisations.
            $event->setSource('ics'); // On marque la source de l'événement.
            $event->setTitle($icsEvent->summary ?? 'Sans titre'); // Le champ 'summary' dans ICS correspond au titre.
            $event->setDescription($icsEvent->description ?? ''); // On s'assure de ne jamais avoir de valeur null.

            // --- GESTION COMPLEXE DES DATES ET FUSEAUX HORAIRES ---
            // On détermine le fuseau horaire à utiliser pour la date de début.
            $startTimezoneToUse = $defaultTimezone;
            if (isset($icsEvent->dtstart_tz) && in_array($icsEvent->dtstart_tz, $validTimezones)) {
                // Si l'événement ICS spécifie un fuseau horaire valide, on l'utilise.
                $startTimezoneToUse = new \DateTimeZone($icsEvent->dtstart_tz);
            }
            // On crée un objet DateTime PHP en utilisant la date et le bon fuseau horaire.
            $event->setStart(new \DateTime($icsEvent->dtstart, $startTimezoneToUse));

            // On fait de même pour la date de fin.
            if (isset($icsEvent->dtend)) {
                $endTimezoneToUse = $defaultTimezone;
                if (isset($icsEvent->dtend_tz) && in_array($icsEvent->dtend_tz, $validTimezones)) {
                    $endTimezoneToUse = new \DateTimeZone($icsEvent->dtend_tz);
                }
                $event->setEnd(new \DateTime($icsEvent->dtend, $endTimezoneToUse));
            } else {
                // Si aucune date de fin n'est fournie, on considère que c'est un événement ponctuel.
                $event->setEnd(clone $event->getStart());
            }

            // Détection des événements "journée entière" (all-day).
            // Dans le format ICS, une date "journée entière" n'a pas de partie "heure" (pas de 'T' dans la chaîne).
            $isAllDay = strpos($icsEvent->dtstart, 'T') === false;
            $event->setAllDay($isAllDay);

            // On dit à Doctrine de "suivre" cette nouvelle entité. Elle n'est pas encore sauvegardée.
            $this->em->persist($event);
            $added++; // On incrémente le compteur des événements ajoutés.
        }

        // --- SAUVEGARDE EN BASE DE DONNÉES ---
        // C'est une optimisation : on ne lance la transaction vers la base de données
        // que s'il y a eu au moins un événement à ajouter.
        if ($added > 0) {
            $this->em->flush(); // 'flush()' exécute les requêtes SQL pour insérer tous les nouveaux événements en une seule fois.
        }

        // On retourne le rapport final.
        return [
            'added' => $added,
            'skipped' => $skipped,
        ];
    }
}
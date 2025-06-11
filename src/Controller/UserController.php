<?php

namespace App\Controller;

// --- Imports des classes nécessaires ---
use App\Repository\UserRepository; // Pour interagir avec la table des utilisateurs
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // La classe de base pour les contrôleurs Symfony
use Symfony\Component\HttpFoundation\Response; // Pour créer des réponses HTTP
use Symfony\Component\Routing\Annotation\Route; // Pour définir les routes avec des attributs PHP
use App\Form\ProfilType; // Le formulaire de modification de profil
use Doctrine\ORM\EntityManagerInterface; // Pour interagir avec la base de données (enregistrer, supprimer)
use Symfony\Component\HttpFoundation\Request; // Pour gérer les requêtes HTTP (ex: données de formulaire)
use Symfony\Component\HttpFoundation\File\Exception\FileException; // Pour gérer les erreurs lors de l'upload de fichiers
use Symfony\Component\String\Slugger\SluggerInterface; // Pour sécuriser les noms de fichiers
use Symfony\Component\Security\Http\Attribute\IsGranted; // Pour restreindre l'accès aux routes

/**
 * Ce contrôleur gère toutes les actions liées aux utilisateurs :
 * - Affichage du trombinoscope
 * - Affichage et modification du profil personnel
 */
class UserController extends AbstractController
{
    /**
     * Affiche la page du trombinoscope avec la liste de tous les utilisateurs.
     * Cette route est publique et accessible à tous.
     *
     * @param UserRepository $userRepository Le service pour récupérer les utilisateurs depuis la base de données.
     * @return Response La page HTML du trombinoscope.
     */
    #[Route('/trombinoscope', name: 'user_trombinoscope')]
    public function trombinoscope(UserRepository $userRepository): Response
    {
        // On utilise le UserRepository pour trouver tous les utilisateurs.
        // C'est la méthode standard pour récupérer des listes d'entités.
        $users = $userRepository->findAll();

        // On rend le template Twig 'trombinoscope.html.twig' et on lui passe
        // la variable 'users' qui contient la liste de nos utilisateurs.
        return $this->render('trombinoscope.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * Affiche la page de profil de l'utilisateur actuellement connecté.
     * 🛡️ SÉCURITÉ : #[IsGranted('ROLE_USER')] garantit que seuls les utilisateurs
     * connectés peuvent accéder à cette page. Les visiteurs anonymes sont
     * automatiquement redirigés vers la page de connexion.
     *
     * @return Response La page HTML du profil de l'utilisateur.
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/profil', name: 'user_profile')]
    public function monProfil(): Response
    {
        // $this->getUser() est une méthode magique de Symfony qui retourne
        // l'objet User de la personne actuellement authentifiée.
        $user = $this->getUser();

        // On rend le template de la page de profil.
        $response = $this->render('profil.html.twig', [
            'user' => $user,
        ]);

        // --- GESTION DU CACHE ---
        // On désactive le cache du navigateur pour cette page.
        // C'est une bonne pratique pour les pages de profil afin de s'assurer
        // que les informations affichées sont toujours à jour, surtout après une modification.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    /**
     * Gère l'affichage et le traitement du formulaire de modification de profil.
     * 🛡️ SÉCURITÉ : L'accès est également restreint aux utilisateurs connectés.
     *
     * @param Request $request L'objet qui contient les données de la requête HTTP (POST, GET, etc.).
     * @param EntityManagerInterface $em Le service de Doctrine pour sauvegarder les changements en base de données.
     * @param SluggerInterface $slugger Un service pour transformer une chaîne de caractères en une version "safe" (slug).
     * @return Response La page HTML du formulaire ou une redirection vers le profil.
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/profil/modifier', name: 'user_profile_edit')]
    public function editProfil(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        // On récupère l'utilisateur connecté.
        $user = $this->getUser();

        // On crée une instance du formulaire 'ProfilType' et on le lie à notre objet $user.
        // Les champs du formulaire seront automatiquement pré-remplis avec les données de l'utilisateur.
        $form = $this->createForm(ProfilType::class, $user);

        // Cette ligne est cruciale : elle prend les données soumises dans la requête (ex: $_POST)
        // et les injecte dans l'objet formulaire pour traitement et validation.
        $form->handleRequest($request);

        // On vérifie si le formulaire a été soumis ET s'il est valide.
        // 'isValid()' vérifie toutes les contraintes de validation (définies dans l'entité/FormType)
        // ET la validité du jeton CSRF pour se protéger des failles de sécurité.
        if ($form->isSubmitted() && $form->isValid()) {
            
            // --- TRAITEMENT DE L'UPLOAD DE L'AVATAR ---
            // On récupère le fichier téléversé depuis le champ 'avatar' du formulaire.
            $avatarFile = $form->get('avatar')->getData();

            // On exécute ce bloc seulement si un nouveau fichier a été envoyé.
            if ($avatarFile) {
                // 🛡️ SÉCURITÉ : On nettoie le nom du fichier pour éviter tout problème.
                // 1. On récupère le nom original sans l'extension.
                $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
                // 2. On utilise le "slugger" pour le transformer en une chaîne sûre (ex: "Mon Fichier" -> "mon-fichier").
                $safeFilename = $slugger->slug($originalFilename);
                // 3. On crée un nom de fichier unique pour éviter les conflits si deux utilisateurs envoient un fichier avec le même nom.
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $avatarFile->guessExtension();

                // On essaie de déplacer le fichier téléversé vers son répertoire de destination.
                try {
                    $avatarFile->move(
                        // 'avatars_directory' est un paramètre défini dans config/services.yaml. C'est une bonne pratique.
                        $this->getParameter('avatars_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Si une erreur survient (ex: permissions d'écriture manquantes), on affiche un message d'erreur.
                    $this->addFlash('danger', 'Erreur lors de l\'upload de l\'avatar.');
                }

                // On met à jour la propriété 'avatar' de l'utilisateur avec le NOUVEAU nom de fichier sécurisé.
                $user->setAvatar($newFilename);
            }

            // --- SAUVEGARDE EN BASE DE DONNÉES ---
            // 'flush()' exécute les requêtes SQL pour sauvegarder toutes les modifications
            // (champs du formulaire + nouvel avatar) en base de données.
            $em->flush();
            
            // On crée un message "flash" qui sera affiché sur la page suivante pour confirmer le succès.
            $this->addFlash('success', 'Profil mis à jour avec succès.');

            // ✅ BONNE PRATIQUE (Post-Redirect-Get) : On redirige l'utilisateur vers la page de profil
            // pour éviter qu'il ne puisse resoumettre le formulaire en rafraîchissant la page.
            return $this->redirectToRoute('user_profile');
        }

        // Si le formulaire n'a pas été soumis ou n'est pas valide,
        // on affiche à nouveau la page du formulaire (avec les erreurs s'il y en a).
        return $this->render('edit_profile.html.twig', [
            // 'createView()' prépare le formulaire pour qu'il puisse être affiché par Twig.
            'form' => $form->createView(),
        ]);
    }
}
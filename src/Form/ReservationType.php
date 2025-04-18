<?php
// src/Form/ReservationType.php

namespace App\Form;

use App\Entity\Materiel;
use App\Entity\Reservation;
use App\Repository\MaterielRepository; // Important pour la requête personnalisée
use Symfony\Bridge\Doctrine\Form\Type\EntityType; // Pour la liste de matériel
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType; // Pour les champs date/heure
use Symfony\Component\Form\Extension\Core\Type\NumberType; // Pour latitude/longitude
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual; // Pour la validation des dates
use Symfony\Component\Validator\Constraints\NotNull; // Pour les champs requis

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ pour sélectionner le matériel
            ->add('materiel', EntityType::class, [
                // Spécifie l'entité à utiliser pour peupler la liste
                'class' => Materiel::class,

                // Utilise la propriété 'nom' de l'entité Materiel pour l'affichage dans la liste
                'choice_label' => 'nom',

                // Label affiché à côté du champ
                'label' => 'Matériel à réserver',

                // Texte affiché comme première option (vide)
                'placeholder' => 'Choisir un matériel...',

                // === Logique clé : Filtrer pour n'afficher que le matériel libre ===
                'query_builder' => function (MaterielRepository $materielRepository) {
                    return $materielRepository->createQueryBuilder('m') // 'm' est l'alias pour Materiel
                        ->where('m.etat = :etatLibre') // Conditionne sur l'état
                        ->setParameter('etatLibre', Materiel::ETAT_LIBRE) // Définit la valeur du paramètre (utilise la constante de l'entité !)
                        ->orderBy('m.nom', 'ASC'); // Trie la liste par nom alphabétique
                },

                // Contrainte : L'utilisateur DOIT sélectionner un matériel
                'constraints' => [
                    new NotNull(['message' => 'Veuillez sélectionner un matériel.']),
                ]
            ])

            // Champ pour la date de début
            ->add('dateDebut', DateTimeType::class, [
                'widget' => 'single_text', // Utilise un seul champ input type="datetime-local" HTML5
                'label' => 'Début de la réservation',
                'constraints' => [
                    new NotNull(['message' => 'La date de début est requise.']),
                    // Contrainte : La date doit être aujourd'hui ou dans le futur
                    new GreaterThanOrEqual([
                        'value' => 'today', // Compare à minuit aujourd'hui
                        'message' => 'La date de début ne peut pas être dans le passé.',
                    ]),
                ],
                // Attributs HTML pour le champ (ex: pour le minimum côté client)
                'attr' => [
                    'min' => (new \DateTime())->format('Y-m-d\TH:i'),
                ]
            ])

            // Champ pour la date de fin
            ->add('dateFin', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Fin de la réservation',
                'constraints' => [
                    new NotNull(['message' => 'La date de fin est requise.']),
                    // !! IMPORTANT : Ajouter une validation pour s'assurer que dateFin > dateDebut
                    // Ceci est souvent fait via une contrainte de classe sur l'entité Reservation
                    // ou via une contrainte Callback dans ce formulaire (voir exemple précédent).
                ],
                'attr' => [
                     'min' => (new \DateTime())->format('Y-m-d\TH:i'),
                ]
            ])

            // Champ pour la latitude (optionnel)
            ->add('latitude', NumberType::class, [
                'label' => 'Latitude (optionnel)',
                'required' => false, // Ce champ n'est pas obligatoire
                'html5' => true, // Utilise input type="number" avec step="any"
                'scale' => 6, // Autorise jusqu'à 6 chiffres après la virgule
                 'attr' => [
                     'step' => 'any' // Nécessaire pour les nombres décimaux dans certains navigateurs
                 ]
            ])

            // Champ pour la longitude (optionnel)
            ->add('longitude', NumberType::class, [
                'label' => 'Longitude (optionnel)',
                'required' => false,
                'html5' => true,
                'scale' => 6,
                 'attr' => [
                     'step' => 'any'
                 ]
            ])
            // Si tu avais besoin du nom du locataire, tu l'ajouterais ici avec TextType::class
            // ->add('nomLocataire', TextType::class, ['label' => 'Nom du locataire'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Lie ce formulaire à l'entité Reservation
            'data_class' => Reservation::class,
            // Tu peux ajouter des validations globales au formulaire ici si besoin
        ]);
    }
}
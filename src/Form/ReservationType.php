<?php
// src/Form/ReservationType.php

namespace App\Form;

use App\Entity\Materiel;
use App\Entity\Reservation;
use App\Entity\User; // Ajouté pour le champ User en mode admin
use App\Repository\MaterielRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotNull;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isAdminEdit = $options['is_admin_edit']; // Récupérer la valeur de l'option

        $builder
            ->add('materiel', EntityType::class, [
                'class' => Materiel::class,
                'choice_label' => 'nom',
                'label' => 'Matériel à réserver',
                'placeholder' => 'Choisir un matériel...',
                'disabled' => $isAdminEdit, // Désactiver si admin édite (pour ne pas changer le matériel d'une résa existante facilement)
                'query_builder' => function (MaterielRepository $materielRepository) use ($options) {
                    // Si ce n'est pas une édition admin ET que c'est une nouvelle réservation (pas d'ID sur la data)
                    // alors on filtre par matériel libre.
                    // $options['data'] est l'objet Reservation passé au formulaire.
                    if (!$options['is_admin_edit'] && !$options['data']?->getId()) {
                        return $materielRepository->createQueryBuilder('m')
                            ->where('m.etat = :etatLibre')
                            ->setParameter('etatLibre', Materiel::ETAT_LIBRE)
                            ->orderBy('m.nom', 'ASC');
                    }
                    // Sinon (admin edit OU user modifie sa propre réservation OU matériel pré-sélectionné)
                    // on affiche tous les matériels pour que celui déjà associé reste sélectionné/visible.
                    return $materielRepository->createQueryBuilder('m')
                        ->orderBy('m.nom', 'ASC');
                },
                'constraints' => [
                    new NotNull(['message' => 'Veuillez sélectionner un matériel.']),
                ]
            ]);

        // Si c'est un admin qui édite, on lui permet de changer l'utilisateur associé
        if ($isAdminEdit) {
            $builder->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    $displayName = trim($user->getPrenom() . ' ' . $user->getNom());
                    return $displayName . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Membre (locataire)',
                'placeholder' => 'Sélectionnez un membre',
                'required' => true, // En mode admin, l'utilisateur doit être spécifié
                'constraints' => [
                    new NotNull(['message' => 'Veuillez sélectionner un utilisateur.']),
                ]
            ]);
        }
        // Pour un utilisateur normal créant une réservation, l'utilisateur est
        // défini dans le contrôleur ($reservation->setUser($this->getUser());)


        $builder
            ->add('dateDebut', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Début de la réservation',
                'html5' => true, // Assure une meilleure compatibilité avec les navigateurs pour datetime-local
                'constraints' => [
                    new NotNull(['message' => 'La date de début est requise.']),
                    new GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date de début ne peut pas être dans le passé.',
                    ]),
                ],
                'attr' => [
                    // 'min' est mieux géré dynamiquement ou via JS pour la comparaison avec dateFin
                    // La contrainte GreaterThanOrEqual s'en charge côté serveur.
                ]
            ])
            ->add('dateFin', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Fin de la réservation',
                'html5' => true,
                'constraints' => [
                    new NotNull(['message' => 'La date de fin est requise.']),
                    // Une contrainte de classe sur l'entité Reservation pour vérifier dateFin > dateDebut
                    // est la meilleure solution (ex: @Assert\Expression("this.getDateFin() > this.getDateDebut()", message="..."))
                ],
                'attr' => [
                    // 'min' est mieux géré dynamiquement ou via JS
                ]
            ])
            ->add('latitude', NumberType::class, [
                'label' => 'Latitude (optionnel)',
                'required' => false,
                'html5' => true,
                'scale' => 6,
                 'attr' => [
                     'step' => 'any'
                 ]
            ])
            ->add('longitude', NumberType::class, [
                'label' => 'Longitude (optionnel)',
                'required' => false,
                'html5' => true,
                'scale' => 6,
                 'attr' => [
                     'step' => 'any'
                 ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'is_admin_edit' => false, // <<<<---- DÉFINITION DE L'OPTION PERSONNALISÉE
            // Vous pouvez ajouter une option pour la validation des dates ici si vous ne le faites pas sur l'entité
            // 'constraints' => [
            //     new Callback([$this, 'validateDates']), // Exemple de validation par callback
            // ],
        ]);

        // Définir le type autorisé pour votre option personnalisée
        $resolver->setAllowedTypes('is_admin_edit', 'bool');
    }

    // Si vous utilisez une contrainte Callback pour valider que dateFin > dateDebut
    // public function validateDates(Reservation $reservation, ExecutionContextInterface $context): void
    // {
    //     if ($reservation->getDateDebut() && $reservation->getDateFin()) {
    //         if ($reservation->getDateFin() <= $reservation->getDateDebut()) {
    //             $context->buildViolation('La date de fin doit être postérieure à la date de début.')
    //                 ->atPath('dateFin') // Lie l'erreur au champ dateFin
    //                 ->addViolation();
    //         }
    //     }
    // }
}
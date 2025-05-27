<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\Materiel;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
// Si vous aviez un champ nomLocataire (que nous avons discuté de supprimer)
// use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
// Si vous avez besoin de Security, par exemple pour pré-remplir l'utilisateur
// use Symfony\Component\Security\Core\Security;

class RegistrationFormType extends AbstractType
{
    // Si vous injectez des services, par exemple Security :
    // private $security;
    // public function __construct(Security $security)
    // {
    //     $this->security = $security;
    // }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Récupération de la valeur de l'option personnalisée
        $isAdminEdit = $options['is_admin_edit'];

        $builder
            ->add('dateDebut', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début de la réservation',
                // 'html5' => true, // Normalement géré par 'single_text'
            ])
            ->add('dateFin', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin de la réservation',
                // 'html5' => true,
            ])
            ->add('materiel', EntityType::class, [
                'class' => Materiel::class,
                'choice_label' => 'nom',
                'label' => 'Matériel',
                // On désactive le champ matériel si c'est un admin qui édite,
                // car la logique de changement de matériel en cours de réservation
                // peut être complexe (gestion des états, etc.)
                'disabled' => $isAdminEdit,
                'query_builder' => function (EntityRepository $er) use ($options) {
                    // Si ce n'est pas une édition admin ET que c'est une nouvelle réservation
                    // (pas d'ID existant sur l'objet $options['data'] qui est la Reservation)
                    // alors on filtre par matériel libre.
                    if (!$options['is_admin_edit'] && !$options['data']?->getId()) {
                         return $er->createQueryBuilder('m')
                            ->where('m.etat = :etat_libre')
                            ->setParameter('etat_libre', Materiel::ETAT_LIBRE)
                            ->orderBy('m.nom', 'ASC');
                    }
                    // Sinon (admin edit OU user voit une réservation existante OU matériel pré-sélectionné)
                    // on affiche tous les matériels pour que le matériel concerné reste sélectionné.
                    return $er->createQueryBuilder('m')
                        ->orderBy('m.nom', 'ASC');
                },
            ]);

        // Si c'est un admin qui édite, on lui permet de changer l'utilisateur associé
        if ($isAdminEdit) {
            $builder->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return trim($user->getPrenom() . ' ' . $user->getNom()) . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Membre (locataire)',
                'placeholder' => 'Sélectionnez un membre', // Optionnel
            ]);
        }
        // Pour un utilisateur normal créant une réservation, l'utilisateur est
        // défini dans le contrôleur ($reservation->setUser($this->getUser());)

        // Le champ nomLocataire est probablement redondant si vous liez toujours à un User.
        // Si vous l'avez gardé :
        /*
        $builder->add('nomLocataire', TextType::class, [
            'label' => 'Nom du locataire (si différent)',
            'required' => false,
        ]);
        */

        $builder
            ->add('latitude', NumberType::class, [
                'label' => 'Latitude (optionnel)',
                'required' => false,
                'scale' => 6, // Précision des décimales
                'html5' => true, // Pour utiliser input type="number" avec step, etc.
            ])
            ->add('longitude', NumberType::class, [
                'label' => 'Longitude (optionnel)',
                'required' => false,
                'scale' => 6,
                'html5' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            // C'est ICI qu'on définit l'option personnalisée et sa valeur par défaut
            'is_admin_edit' => false,
        ]);

        // C'est une bonne pratique de définir aussi les types autorisés pour vos options
        $resolver->setAllowedTypes('is_admin_edit', 'bool');
    }
}
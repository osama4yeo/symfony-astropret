<?php
// src/Form/MaterielType.php

namespace App\Form;

use App\Entity\Materiel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType; // Important pour l'upload
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File; // Pour la validation du fichier

class MaterielType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du matériel',
            ])
            // Si vous réintroduisez la description
            // ->add('description', TextareaType::class, [
            //     'label' => 'Description (optionnel)',
            //     'required' => false,
            // ])
            ->add('etat', ChoiceType::class, [
                'label' => 'État actuel',
                'choices' => [
                    'Libre' => Materiel::ETAT_LIBRE,
                    'Loué' => Materiel::ETAT_LOUE,
                    // 'En maintenance' => Materiel::ETAT_MAINTENANCE, // Si vous avez cet état
                ],
            ])
            ->add('imageFile', FileType::class, [ // Nom du champ pour le fichier uploadé
                'label' => 'Image du matériel (JPG, PNG)',
                'mapped' => false, // Ne pas mapper directement à la propriété imageFilename
                'required' => false, // Mettre à true si une image est obligatoire à la création
                'constraints' => [
                    new File([
                        'maxSize' => '2048k', // 2MB max
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif', // Si vous autorisez les gifs
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, GIF).',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Materiel::class,
        ]);
    }
}

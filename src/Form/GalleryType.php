<?php
// src/Form/GalleryType.php
namespace App\Form;

use App\Entity\Gallery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class GalleryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = $options['is_new'];

        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de l\'image',
                'attr' => ['placeholder' => 'Ex: Nébuleuse d\'Orion'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description (optionnel)',
                'required' => false,
                'attr' => ['rows' => 5, 'placeholder' => 'Courte description de l\'image...'],
            ])
            ->add('imageFile', FileType::class, [ // Assurez-vous que toutes les parenthèses et crochets sont équilibrés ici
                'label' => 'Fichier image (JPG, PNG, GIF - max 2Mo)',
                'mapped' => false,
                'required' => $isNew,
                'constraints' => [
                    new Image([ // Début du constructeur de Image
                        'maxSize' => '2048k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ], // Fin du tableau mimeTypes
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, GIF).',
                    ]), // Fin du constructeur de Image, et fin de l'élément du tableau de contraintes
                ], // Fin du tableau 'constraints'
                'attr' => [
                    'accept' => 'image/jpeg, image/png, image/gif'
                ] // Fin du tableau 'attr'
            ]) // Fin de ->add('imageFile')
            ->add('ordreAffichage', IntegerType::class, [
                'label' => 'Ordre d\'affichage (optionnel, ex: 1, 2, 3... Les plus petits en premier)',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: 1'],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Afficher cette image dans la galerie ?',
                'required' => false,
            ]); // Fin du dernier ->add()
    } // Fin de la méthode buildForm

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Gallery::class,
            'is_new' => false,
        ]);

        $resolver->setAllowedTypes('is_new', 'bool');
    } // Fin de la méthode configureOptions
} // Fin de la classe GalleryType
<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType; // Utiliser EmailType pour le champ email
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType; // Pour la confirmation du mot de passe
use Symfony\Component\Form\Extension\Core\Type\TextType;     // Pour Prénom, Nom, Téléphone
use Symfony\Component\Form\Extension\Core\Type\DateType;    // Pour Date de Naissance
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
// Optionnel: si tu veux ajouter une contrainte de complexité Regex pour le mot de passe
// use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                // Si le prénom est obligatoire, décommente la contrainte NotBlank
                // et assure-toi que 'nullable: false' est sur l'entité (ou une contrainte NotBlank sur l'entité)
                // 'constraints' => [
                //     new NotBlank(['message' => 'Veuillez entrer votre prénom.']),
                // ],
                'required' => true, // Mettre à true si obligatoire et contrainte NotBlank activée
                                    // La valeur de 'required' ici contrôle l'attribut HTML5 'required'
                                    // La validation se fait via les contraintes
                'attr' => ['placeholder' => 'Votre prénom'],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom de famille',
                // 'constraints' => [
                //     new NotBlank(['message' => 'Veuillez entrer votre nom de famille.']),
                // ],
                'required' => true,
                'attr' => ['placeholder' => 'Votre nom de famille'],
            ])
            ->add('telephone', TextType::class, [ // TextType est souvent utilisé pour les numéros de tél. pour flexibilité de format
                'label' => 'Numéro de téléphone',
                'required' => true, // Rendre optionnel
                'attr' => ['placeholder' => 'Ex: 0612345678'],
                // Optionnel: ajouter une contrainte de format pour le téléphone
                // 'constraints' => [
                //     new Regex([
                //         'pattern' => '/^(?:(?:\+|00)33[\s.-]{0,3}(?:\(0\)[\s.-]{0,3})?|0)[1-9](?:(?:[\s.-]?\d{2}){4}|\d{2}(?:[\s.-]?\d{3}){2})$/',
                //         'message' => 'Veuillez entrer un numéro de téléphone français valide.'
                //     ])
                // ]
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text', // Pour un champ input type="date" HTML5
                'required' => true, // Rendre optionnel
                'html5' => true, // Important pour le bon rendu du widget single_text
                'attr' => ['placeholder' => 'JJ/MM/AAAA'],
                // Optionnel: ajouter une contrainte pour l'âge (ex: l'utilisateur doit être majeur)
                // 'constraints' => [
                //     new LessThanOrEqual([
                //         'value' => (new \DateTimeImmutable('-18 years'))->format('Y-m-d'), // Doit avoir au moins 18 ans
                //         'message' => 'Vous devez avoir au moins 18 ans pour vous inscrire.'
                //     ])
                // ]
            ])
            ->add('email', EmailType::class, [ // --- MODIFICATION 1: Utiliser EmailType ---
                'label' => 'Adresse Email', // Label en français
                'attr' => [
                    'placeholder' => 'exemple@domaine.com' // Placeholder
                ],
                'constraints' => [ // Tu peux ajouter des contraintes NotBlank et Email ici si elles ne sont pas sur l'entité
                    new NotBlank([
                        'message' => 'Veuillez entrer une adresse email.',
                    ]),
                    // La contrainte Email sur l'entité est généralement suffisante si le champ est bien mappé.
                ],
            ])
            // --- MODIFICATION 2: Remplacer PasswordType par RepeatedType pour la confirmation ---
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class, // Le type de champ pour chaque répétition
                'first_options'  => [
                    'label' => 'Mot de passe',
                    'attr' => ['autocomplete' => 'new-password'], // Pour le gestionnaire de mots de passe
                    'hash_property_path' => 'password' // Optionnel avec les nouvelles versions, Symfony le gère bien
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                // Ne pas mapper ce champ directement à la propriété 'password' de l'entité
                // car on va le hacher dans le contrôleur.
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe.',
                    ]),
                    new Length([
                        'min' => 8, // Augmenter la longueur minimale est une bonne pratique
                        'minMessage' => 'Votre mot de passe doit comporter au moins {{ limit }} caractères.',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                    // Optionnel: Ajoute une contrainte Regex pour la complexité du mot de passe
                    // new Regex([
                    //     'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
                    //     'message' => 'Le mot de passe doit contenir au moins une minuscule, une majuscule, un chiffre et un caractère spécial (@$!%*?&).'
                    // ])
                ],
            ])
            
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false, // Ne pas mapper à une propriété de l'entité User
                'label' => 'J\'accepte les conditions d\'utilisation', // Label en français
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter nos conditions d\'utilisation.', // Message en français
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
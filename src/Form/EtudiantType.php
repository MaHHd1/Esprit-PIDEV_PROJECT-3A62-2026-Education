<?php

namespace App\Form;

use App\Entity\Etudiant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EtudiantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire.']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\'-]+$/u',
                        'message' => 'Le nom ne peut contenir que des lettres, espaces, apostrophes et tirets.'
                    ])
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prénom est obligatoire.']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\'-]+$/u',
                        'message' => 'Le prénom ne peut contenir que des lettres, espaces, apostrophes et tirets.'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'email est obligatoire.']),
                    new Assert\Email(['message' => 'L\'email {{ value }} n\'est pas valide.']),
                    new Assert\Length(['max' => 180])
                ]
            ])
            ->add('motDePasse', PasswordType::class, [
                'label' => 'Mot de passe',
                'required' => !$options['is_edit'],
                'mapped' => !$options['is_edit'],
                'constraints' => $options['is_edit'] ? [] : [
                    new Assert\NotBlank(['message' => 'Le mot de passe est obligatoire.']),
                    new Assert\Length([
                        'min' => 8,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                        'message' => 'Le mot de passe doit contenir au moins une minuscule, une majuscule et un chiffre.'
                    ])
                ]
            ])
            ->add('matricule', TextType::class, [
                'label' => 'Matricule',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le matricule est obligatoire.']),
                    new Assert\Length([
                        'min' => 5,
                        'max' => 50,
                        'minMessage' => 'Le matricule doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le matricule ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[A-Z0-9-]+$/',
                        'message' => 'Le matricule ne peut contenir que des lettres majuscules, chiffres et tirets.'
                    ])
                ]
            ])
            ->add('niveauEtude', ChoiceType::class, [
                'label' => 'Niveau d\'étude',
                'choices' => [
                    'Licence 1' => 'Licence 1',
                    'Licence 2' => 'Licence 2',
                    'Licence 3' => 'Licence 3',
                    'Master 1' => 'Master 1',
                    'Master 2' => 'Master 2',
                    'Doctorat' => 'Doctorat'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le niveau d\'étude est obligatoire.']),
                    new Assert\Choice([
                        'choices' => ['Licence 1', 'Licence 2', 'Licence 3', 'Master 1', 'Master 2', 'Doctorat'],
                        'message' => 'Veuillez choisir un niveau d\'étude valide.'
                    ])
                ]
            ])
            ->add('specialisation', TextType::class, [
                'label' => 'Spécialisation',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La spécialisation est obligatoire.']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'La spécialisation doit contenir au moins {{ limit }} caractères.'
                    ])
                ]
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de naissance est obligatoire.']),
                    new Assert\LessThan([
                        'value' => '-16 years',
                        'message' => 'L\'étudiant doit avoir au moins 16 ans.'
                    ]),
                    new Assert\GreaterThan([
                        'value' => '-100 years',
                        'message' => 'La date de naissance n\'est pas valide.'
                    ])
                ]
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le téléphone est obligatoire.']),
                    new Assert\Regex([
                        'pattern' => '/^(\+?[0-9]{1,3})?[0-9]{8,15}$/',
                        'message' => 'Le numéro de téléphone n\'est pas valide.'
                    ])
                ]
            ])
            ->add('adresse', TextareaType::class, [
                'label' => 'Adresse',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'adresse est obligatoire.']),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 500,
                        'minMessage' => 'L\'adresse doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'L\'adresse ne peut pas dépasser {{ limit }} caractères.'
                    ])
                ]
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => 'actif',
                    'Inactif' => 'inactif',
                    'Diplômé' => 'diplome',
                    'Suspendu' => 'suspendu'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le statut est obligatoire.']),
                    new Assert\Choice([
                        'choices' => ['actif', 'inactif', 'diplome', 'suspendu'],
                        'message' => 'Statut invalide.'
                    ])
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => $options['is_edit'] ? 'Modifier' : 'Créer'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Etudiant::class,
            'is_edit' => false
        ]);
    }
}

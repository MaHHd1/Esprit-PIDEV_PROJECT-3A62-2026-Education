<?php

namespace App\Form;

use App\Entity\Administrateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AdministrateurType extends AbstractType
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
            ->add('departement', ChoiceType::class, [
                'label' => 'Département',
                'choices' => [
                    'Direction Générale' => 'Direction Générale',
                    'Scolarité' => 'Scolarité',
                    'Ressources Humaines' => 'Ressources Humaines',
                    'Finances' => 'Finances',
                    'Informatique' => 'Informatique',
                    'Communication' => 'Communication',
                    'Recherche' => 'Recherche'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le département est obligatoire.']),
                    new Assert\Choice([
                        'choices' => [
                            'Direction Générale',
                            'Scolarité',
                            'Ressources Humaines',
                            'Finances',
                            'Informatique',
                            'Communication',
                            'Recherche'
                        ],
                        'message' => 'Veuillez choisir un département valide.'
                    ])
                ]
            ])
            ->add('fonction', TextType::class, [
                'label' => 'Fonction',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La fonction est obligatoire.']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'La fonction doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'La fonction ne peut pas dépasser {{ limit }} caractères.'
                    ])
                ]
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^(\+?[0-9]{1,3})?[0-9]{8,15}$/',
                        'message' => 'Le numéro de téléphone n\'est pas valide.'
                    ])
                ]
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Compte actif',
                'required' => false
            ])
            ->add('submit', SubmitType::class, [
                'label' => $options['is_edit'] ? 'Modifier' : 'Créer'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Administrateur::class,
            'is_edit' => false
        ]);
    }
}

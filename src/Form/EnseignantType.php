<?php

namespace App\Form;

use App\Entity\Enseignant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EnseignantType extends AbstractType
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
            ->add('matriculeEnseignant', TextType::class, [
                'label' => 'Matricule Enseignant',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le matricule enseignant est obligatoire.']),
                    new Assert\Length([
                        'min' => 5,
                        'max' => 50,
                        'minMessage' => 'Le matricule doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le matricule ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^ENS-[A-Z0-9-]+$/',
                        'message' => 'Le matricule doit commencer par ENS- suivi de lettres majuscules et chiffres.'
                    ])
                ]
            ])
            ->add('diplome', ChoiceType::class, [
                'label' => 'Diplôme',
                'choices' => [
                    'Licence' => 'Licence',
                    'Master' => 'Master',
                    'Doctorat' => 'Doctorat',
                    'HDR' => 'HDR',
                    'Ingénieur' => 'Ingénieur'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le diplôme est obligatoire.']),
                    new Assert\Choice([
                        'choices' => ['Licence', 'Master', 'Doctorat', 'HDR', 'Ingénieur'],
                        'message' => 'Veuillez choisir un diplôme valide.'
                    ])
                ]
            ])
            ->add('specialite', TextType::class, [
                'label' => 'Spécialité',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La spécialité est obligatoire.']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'La spécialité doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'La spécialité ne peut pas dépasser {{ limit }} caractères.'
                    ])
                ]
            ])
            ->add('anneesExperience', IntegerType::class, [
                'label' => 'Années d\'expérience',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Les années d\'expérience sont obligatoires.']),
                    new Assert\Range([
                        'min' => 0,
                        'max' => 50,
                        'notInRangeMessage' => 'Les années d\'expérience doivent être entre {{ min }} et {{ max }}.'
                    ])
                ]
            ])
            ->add('typeContrat', ChoiceType::class, [
                'label' => 'Type de contrat',
                'choices' => [
                    'CDI' => 'CDI',
                    'CDD' => 'CDD',
                    'Vacataire' => 'Vacataire',
                    'Contractuel' => 'Contractuel'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le type de contrat est obligatoire.']),
                    new Assert\Choice([
                        'choices' => ['CDI', 'CDD', 'Vacataire', 'Contractuel'],
                        'message' => 'Veuillez choisir un type de contrat valide.'
                    ])
                ]
            ])
            ->add('tauxHoraire', NumberType::class, [
                'label' => 'Taux horaire (€)',
                'required' => false,
                'constraints' => [
                    new Assert\Callback([
                        'callback' => [$this, 'validateTauxHoraire']
                    ])
                ]
            ])
            ->add('disponibilites', TextareaType::class, [
                'label' => 'Disponibilités',
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'Les disponibilités ne peuvent pas dépasser {{ limit }} caractères.'
                    ])
                ]
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => 'actif',
                    'Inactif' => 'inactif',
                    'Congé' => 'conge',
                    'Retraite' => 'retraite'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le statut est obligatoire.']),
                    new Assert\Choice([
                        'choices' => ['actif', 'inactif', 'conge', 'retraite'],
                        'message' => 'Statut invalide.'
                    ])
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => $options['is_edit'] ? 'Modifier' : 'Créer'
            ]);
    }

    /**
     * Custom validation callback for tauxHoraire field
     */
    public function validateTauxHoraire($value, ExecutionContextInterface $context): void
    {
        $formData = $context->getObject();

        // Get the typeContrat value from the form data
        $typeContrat = null;

        if ($formData instanceof Enseignant) {
            $typeContrat = $formData->getTypeContrat();
        } else {
            // Try to get from the parent context
            $root = $context->getRoot();
            if ($root instanceof Enseignant) {
                $typeContrat = $root->getTypeContrat();
            }
        }

        // If contrat is Vacataire, tauxHoraire is required
        if ($typeContrat === 'Vacataire') {
            if ($value === null || $value === '') {
                $context->buildViolation('Le taux horaire est obligatoire pour les vacataires.')
                    ->addViolation();
            } elseif ($value <= 0) {
                $context->buildViolation('Le taux horaire doit être positif.')
                    ->addViolation();
            } elseif ($value < 10 || $value > 200) {
                $context->buildViolation('Le taux horaire doit être entre 10 et 200 euros.')
                    ->addViolation();
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Enseignant::class,
            'is_edit' => false
        ]);
    }
}
<?php

namespace App\Form;

use App\Entity\Quiz;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuizType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du quiz',
                'attr' => [
                    'placeholder' => 'Ex: Quiz de mathématiques - Chapitre 1',
                    'class' => 'form-control'
                ],
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Décrivez le contenu et les objectifs du quiz...',
                    'class' => 'form-control',
                    'rows' => 4,
                ],
                'required' => false,
            ])
            ->add('idCours', ChoiceType::class, [
                'label' => 'Cours associé',
                'choices' => $options['cours_choices'],
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'placeholder' => 'Aucun cours associé',
            ])
            ->add('typeQuiz', ChoiceType::class, [
                'label' => 'Type de quiz',
                'choices' => [
                    'Évaluation'   => Quiz::TYPE_EVALUATION,    // 'evaluation'
                    'Entraînement' => Quiz::TYPE_ENTRAINEMENT,  // 'entrainement'
                    'Révision'     => Quiz::TYPE_REVISION,      // 'revision'
                    'Diagnostique' => Quiz::TYPE_DIAGNOSTIQUE,  // 'diagnostique'
                ],
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'placeholder' => 'Choisissez un type',
            ])
            ->add('dureeMinutes', IntegerType::class, [
                'label' => 'Durée (en minutes)',
                'attr' => [
                    'placeholder' => '30',
                    'class' => 'form-control',
                    'min' => 1,
                ],
                'required' => false,
            ])
            ->add('nombreTentativesAutorisees', IntegerType::class, [
                'label' => 'Nombre de tentatives autorisées',
                'attr' => [
                    'placeholder' => '3',
                    'class' => 'form-control',
                    'min' => 1,
                ],
                'required' => false,
                'help' => 'Laissez vide pour un nombre illimité',
            ])
            ->add('difficulteMoyenne', NumberType::class, [
                'label' => 'Difficulté moyenne (1-10)',
                'attr' => [
                    'placeholder' => '5',
                    'class' => 'form-control',
                    'step' => '0.1',
                    'min' => 1,
                    'max' => 10,
                ],
                'required' => false,
                'scale' => 1,
            ])
            ->add('instructions', TextareaType::class, [
                'label' => 'Instructions pour les étudiants',
                'attr' => [
                    'placeholder' => 'Consignes spécifiques pour répondre au quiz...',
                    'class' => 'form-control',
                    'rows' => 3,
                ],
                'required' => false,
            ])
            ->add('dateDebutDisponibilite', DateTimeType::class, [
                'label' => 'Disponible à partir du',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'help' => 'Laissez vide pour une disponibilité immédiate',
            ])
            ->add('dateFinDisponibilite', DateTimeType::class, [
                'label' => 'Disponible jusqu\'au',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'help' => 'Laissez vide pour aucune limite',
            ])
            ->add('afficherCorrectionApres', ChoiceType::class, [
                'label' => 'Afficher la correction',
                'choices' => [
                    'Immédiatement après la soumission' => 'immediat',
                    'À une date spécifique'             => 'date',
                    'Jamais'                            => 'jamais',
                ],
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'placeholder' => 'Choisissez une option',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quiz::class,
            'cours_choices' => [],
        ]);
    }
}
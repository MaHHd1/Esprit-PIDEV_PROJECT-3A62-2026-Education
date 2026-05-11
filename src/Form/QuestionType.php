<?php

namespace App\Form;

use App\Entity\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('texte', TextareaType::class, [
                'label' => 'Texte de la question',
                'required' => true,
                'attr' => [
                    'rows' => 4,
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le texte de la question...'
                ],
            ])
            
            // CORRECTED: Use 'type_question' to match entity property
            ->add('type_question', HiddenType::class, [
                'required' => true,
                'label' => false,
                'attr' => [
                    'id' => 'question-type-field',
                    'class' => 'd-none'
                ]
            ])

            ->add('points', IntegerType::class, [
                'label' => 'Points',
                'required' => true,
                'attr' => [
                    'min' => 1,
                    'class' => 'form-control',
                    'placeholder' => '1'
                ],
                'data' => 1,
            ])

            // CORRECTED: Use 'explication_reponse' to match entity property
            ->add('explication_reponse', TextareaType::class, [
                'label' => 'Explication (optionnel)',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'form-control',
                    'placeholder' => 'Explication de la réponse...'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
            'attr' => [
                'id' => 'question-form',
                'novalidate' => 'novalidate',
                'class' => 'question-form'
            ]
        ]);
    }
}
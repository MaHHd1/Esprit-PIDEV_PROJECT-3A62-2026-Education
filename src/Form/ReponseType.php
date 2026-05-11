<?php

namespace App\Form;

use App\Entity\Reponse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('texte_reponse', TextareaType::class, [
                'label' => 'Texte de la réponse',
                'attr' => [
                    'placeholder' => 'Entrez la réponse...',
                    'class' => 'form-control',
                    'rows' => 2,
                ],
                'required' => true,
            ])
            ->add('est_correcte', CheckboxType::class, [
                'label' => 'Cette réponse est correcte',
                'attr' => ['class' => 'form-check-input'],
                'required' => false,
                'label_attr' => ['class' => 'form-check-label'],
            ])
            ->add('pourcentage_points', NumberType::class, [
                'label' => 'Pourcentage des points (%)',
                'attr' => [
                    'placeholder' => '100.00',
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => 0,
                    'max' => 100,
                ],
                'required' => false,
                'scale' => 2,
                'help' => 'Pour les réponses partiellement correctes',
            ])
            ->add('feedback_specifique', TextareaType::class, [
                'label' => 'Feedback spécifique',
                'attr' => [
                    'placeholder' => 'Message personnalisé pour cette réponse...',
                    'class' => 'form-control',
                    'rows' => 2,
                ],
                'required' => false,
                'help' => 'Explication ou indice lié à cette réponse',
            ])
            ->add('media_url', TextType::class, [
                'label' => 'URL d\'un média (optionnel)',
                'attr' => [
                    'placeholder' => 'https://example.com/image.jpg',
                    'class' => 'form-control',
                ],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reponse::class,
        ]);
    }
}
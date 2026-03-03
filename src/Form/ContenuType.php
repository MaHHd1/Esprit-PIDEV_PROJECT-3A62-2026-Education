<?php

namespace App\Form;

use App\Entity\Contenu;
use App\Entity\Cours;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ContenuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cours', EntityType::class, [
                'class' => Cours::class,
                'choice_label' => 'titre',
                'label' => 'Cours parent',
                'choices' => $options['cours_choices'],
                'disabled' => (bool) $options['cours_disabled'],
                'query_builder' => function (EntityRepository $repository) use ($options) {
                    if (!empty($options['cours_choices'])) {
                        $ids = array_map(static fn (Cours $cours) => $cours->getId(), $options['cours_choices']);
                        return $repository->createQueryBuilder('c')
                            ->andWhere('c.id IN (:ids)')
                            ->setParameter('ids', $ids);
                    }

                    return $repository->createQueryBuilder('c')->orderBy('c.titre', 'ASC');
                },
            ])
            ->add('typeContenu', ChoiceType::class, [
                'label' => 'Types de contenu',
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
                'required' => true,
                'choices' => [
                    'Video' => 'video',
                    'PDF' => 'pdf',
                    'PPT' => 'ppt',
                    'Texte' => 'texte',
                    'Quiz' => 'quiz',
                    'Lien' => 'lien',
                ],
                'data' => $options['types_data'],
            ])
            ->add('titre', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('urlContenu', TextType::class, [
                'label' => 'Lien externe (optionnel)',
                'mapped' => false,
                'required' => false,
                'data' => $options['resources_data']['lien'] ?? $options['resources_data']['video_link'] ?? null,
            ])
            ->add('texteContenu', TextareaType::class, [
                'label' => 'Contenu texte',
                'mapped' => false,
                'required' => false,
                'data' => $options['resources_data']['texte'] ?? null,
            ])
            ->add('pdfFile', FileType::class, [
                'label' => 'Fichier PDF',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '25M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Veuillez choisir un fichier PDF valide.',
                    ]),
                ],
            ])
            ->add('pptFile', FileType::class, [
                'label' => 'Fichier PPT/PPTX',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '50M',
                        'mimeTypes' => [
                            'application/vnd.ms-powerpoint',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        ],
                        'mimeTypesMessage' => 'Veuillez choisir un fichier PPT/PPTX valide.',
                    ]),
                ],
            ])
            ->add('videoFile', FileType::class, [
                'label' => 'Fichier video',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '300M',
                        'mimeTypes' => [
                            'video/mp4',
                            'video/webm',
                            'video/quicktime',
                        ],
                        'mimeTypesMessage' => 'Veuillez choisir un fichier video valide.',
                    ]),
                ],
            ])
            ->add('quizExistingId', ChoiceType::class, [
                'label' => 'Quiz existant',
                'mapped' => false,
                'required' => false,
                'placeholder' => 'Selectionner un quiz',
                'choices' => $options['quiz_choices'],
                'choice_value' => static fn ($value) => $value === null ? '' : (string) $value,
                'data' => $options['quiz_selected_data'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Duree (minutes)',
                'required' => false,
            ])
            ->add('ordreAffichage', IntegerType::class, [
                'label' => 'Ordre d\'affichage',
            ])
            ->add('estPublic', CheckboxType::class, [
                'label' => 'Contenu public',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contenu::class,
            'types_data' => ['texte'],
            'resources_data' => [],
            'cours_choices' => [],
            'cours_disabled' => false,
            'quiz_choices' => [],
            'quiz_selected_data' => null,
        ]);
    }
}

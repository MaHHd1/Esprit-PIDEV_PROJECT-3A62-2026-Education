<?php

namespace App\Form;

use App\Entity\ForumDiscussion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ForumDiscussionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr'  => ['placeholder' => 'Titre du forum...'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr'  => [
                    'placeholder' => 'Décrivez le sujet du forum...',
                    'rows'        => 5,
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label'   => 'Type',
                'choices' => [
                    'Public'     => 'public',
                    'Privé'      => 'prive',
                    'Par classe' => 'par_classe',
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => [
                    'Ouvert'   => 'ouvert',
                    'Fermé'    => 'ferme',
                    'Épinglé'  => 'epingle',
                ],
            ])

            // ✅ Image de couverture — upload fichier (remplace l'URL)
            ->add('imageCouvertureFile', VichImageType::class, [
                'required'      => false,
                'label'         => 'Image de couverture',
                'allow_delete'  => true,
                'download_uri'  => false,
                'image_uri'     => true,
                'attr'          => ['accept' => 'image/jpeg,image/png,image/webp,image/gif'],
                'help'          => 'Formats acceptés : JPG, PNG, WEBP, GIF — max 5 Mo',
            ])

            // ✅ Pièce jointe — upload fichier
            ->add('pieceJointeFile', VichFileType::class, [
                'required'     => false,
                'label'        => 'Pièce jointe',
                'allow_delete' => true,
                'download_uri' => true,
                'attr'         => ['accept' => 'image/*,application/pdf,application/zip'],
                'help'         => 'Formats acceptés : images, PDF, ZIP — max 10 Mo',
            ])

            ->add('reglesModeration', TextareaType::class, [
                'required' => false,
                'label'    => 'Règles de modération',
                'attr'     => [
                    'placeholder' => 'Règles à respecter dans ce forum...',
                    'rows'        => 3,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ForumDiscussion::class,
        ]);
    }
}
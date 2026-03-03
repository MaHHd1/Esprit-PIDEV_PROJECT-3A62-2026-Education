<?php

namespace App\Form;

use App\Entity\Evenement;
use App\Enum\StatutEvenement;
use App\Enum\TypeEvenement;
use App\Enum\VisibiliteEvenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de l\'événement',
                'attr' => ['placeholder' => 'Ex : Atelier Symfony Avancé'],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 5],
            ])

            ->add('typeEvenement', EnumType::class, [
                'class'        => TypeEvenement::class,
                'label'        => 'Type d\'événement',
                'choice_label' => fn (TypeEvenement $enum) => ucfirst($enum->value),
                'placeholder'  => 'Choisir un type',
                'required'     => true,
            ])

            ->add('dateDebut', DateTimeType::class, [
                'label'       => 'Date et heure de début',
                'widget'      => 'single_text',
                'html5'       => false,
                'attr'        => ['class' => 'datetimepicker form-control'],
                'required'    => true,
            ])

            ->add('dateFin', DateTimeType::class, [
                'label'       => 'Date et heure de fin',
                'widget'      => 'single_text',
                'html5'       => false,
                'attr'        => ['class' => 'datetimepicker form-control'],
                'required'    => true,
            ])

            ->add('lieu', TextType::class, [
                'label'     => 'Lieu ou lien virtuel',
                'required'  => false,
                'attr'      => ['placeholder' => 'Salle A-12 ou https://zoom.us/...'],
            ])

            ->add('capaciteMax', IntegerType::class, [
                'label'     => 'Capacité maximale',
                'required'  => false,
                'attr'      => [
                    'min'         => 1,
                    'placeholder' => 'Laissez vide pour illimité',
                ],
            ])

            ->add('statut', EnumType::class, [
                'class'        => StatutEvenement::class,
                'label'        => 'Statut',
                'choice_label' => fn (StatutEvenement $enum) => ucfirst($enum->value),
                'placeholder'  => 'Choisir un statut',
                'required'     => true,
            ])

            ->add('visibilite', EnumType::class, [
                'class'        => VisibiliteEvenement::class,
                'label'        => 'Visibilité',
                'choice_label' => fn (VisibiliteEvenement $enum) => ucfirst($enum->value),
                'placeholder'  => 'Choisir la visibilité',
                'required'     => true,
            ])

            // Le champ 'createur' n'est PAS ajouté ici
            // → il est assigné automatiquement dans le controller
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}
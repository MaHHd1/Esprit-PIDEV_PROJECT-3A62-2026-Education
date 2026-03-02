<?php

namespace App\Form;

use App\Entity\Evenement;
use App\Entity\ParticipationEvenement;
use App\Entity\Utilisateur;
use App\Enum\StatutParticipation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipationEvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Événement (relation ManyToOne)
            ->add('evenement', EntityType::class, [
                'class' => Evenement::class,
                'choice_label' => 'titre',
                'placeholder' => 'Sélectionner un événement',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])

            // Utilisateur (relation ManyToOne)
            ->add('utilisateur', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => function (Utilisateur $user) {
                    return $user->getEmail(); // ou getUsername() si tu as ce champ
                },
                'placeholder' => 'Sélectionner un utilisateur',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])

            // Statut (Enum)
            ->add('statut', EnumType::class, [
                'class' => StatutParticipation::class,
                'choice_label' => fn (StatutParticipation $enum) => ucfirst($enum->value),
                'placeholder' => 'Choisir un statut',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])

            // Date d'inscription
            ->add('dateInscription', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'datetimepicker form-control'],
                'required' => true,
            ])

            // Heure d'arrivée
            ->add('heureArrivee', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'datetimepicker form-control'],
                'required' => false,
                'label' => 'Heure d\'arrivée (optionnel)',
            ])

            // Heure de départ
            ->add('heureDepart', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'datetimepicker form-control'],
                'required' => false,
                'label' => 'Heure de départ (optionnel)',
            ])

            // Note de feedback (1 à 5)
            ->add('feedbackNote', IntegerType::class, [
                'label' => 'Note (1 à 5)',
                'attr' => [
                    'min' => 1,
                    'max' => 5,
                    'placeholder' => 'Optionnel',
                ],
                'required' => false,
            ])

            // Commentaire de feedback
            ->add('feedbackCommentaire', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => 'Votre commentaire...'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ParticipationEvenement::class,
        ]);
    }
}
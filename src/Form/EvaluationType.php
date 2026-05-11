<?php
// src/Form/EvaluationType.php
namespace App\Form;

use App\Entity\Cours;
use App\Entity\Evaluation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EvaluationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de l\'évaluation',
                'attr' => ['class' => 'form-control']
            ])
            ->add('typeEvaluation', ChoiceType::class, [
                'label' => 'Type d\'évaluation',
                'choices' => [
                    'Projet' => 'projet',
                    'Examen' => 'examen',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('cours', EntityType::class, [
                'label' => 'Cours',
                'class' => Cours::class,
                'choice_label' => function (Cours $cours) {
                    return $cours->getCodeCours() . ' - ' . $cours->getTitre();
                },
                'placeholder' => 'Sélectionner un cours',
                'attr' => ['class' => 'form-control']
            ])
            ->add('idEnseignant', TextType::class, [
                'label' => 'ID Enseignant',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateLimite', DateTimeType::class, [
                'label' => 'Date limite',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('noteMax', NumberType::class, [
                'label' => 'Note maximale',
                'attr' => ['class' => 'form-control', 'step' => '0.01']
            ])
            ->add('modeRemise', ChoiceType::class, [
                'label' => 'Mode de remise',
                'choices' => [
                    'En ligne' => 'en_ligne',
                    'Présentiel' => 'presentiel',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Ouverte' => 'ouverte',
                    'Fermée' => 'fermee',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('pdfFile', FileType::class, [
                'label' => 'Fichier PDF (Énoncé)',
                'required' => false,
                'mapped' => true,
                'attr' => ['class' => 'form-control', 'accept' => '.pdf'],
                'help' => 'Téléchargez le fichier PDF de l\'évaluation (max 10Mo)'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evaluation::class,
        ]);
    }
}
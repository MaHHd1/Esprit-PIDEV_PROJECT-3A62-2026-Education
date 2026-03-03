<?php
// src/Form/ScoreType.php
namespace App\Form;

use App\Entity\Score;
use App\Entity\Soumission;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScoreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Le champ soumission n'est affiché que lors de la création
        if (!($options['is_edit'] ?? false)) {
            // Mode création : afficher le champ soumission
            $builder->add('soumission', EntityType::class, [
                'class' => Soumission::class,
                'choice_label' => function(Soumission $soumission) {
                    return $soumission->getEvaluation()->getTitre() . ' - ' . $soumission->getIdEtudiant() . 
                           ' (soumis le: ' . $soumission->getDateSoumission()->format('d/m/Y') . ')';
                },
                'choice_attr' => function(Soumission $soumission) {
                    return ['data-note-max' => $soumission->getEvaluation()->getNoteMax()];
                },
                'label' => 'Soumission',
                'attr' => [
                    'class' => 'form-control',
                    'data-controller' => 'soumission-select'
                ]
            ]);
        }
        
        $builder
            ->add('note', NumberType::class, [
                'label' => 'Note obtenue',
                'attr' => ['class' => 'form-control', 'step' => '0.01']
            ])
            ->add('noteSur', NumberType::class, [
                'label' => 'Note sur',
                'attr' => ['class' => 'form-control', 'step' => '0.01']
            ])
            ->add('commentaireEnseignant', TextareaType::class, [
                'label' => 'Commentaire de l\'enseignant',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Score::class,
            'is_edit' => false,
        ]);
    }
}
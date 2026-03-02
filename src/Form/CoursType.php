<?php

namespace App\Form;

use App\Entity\Cours;
use App\Entity\Enseignant;
use App\Entity\Module;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CoursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $showEnseignants = (bool) ($options['show_enseignants'] ?? true);

        $builder
            ->add('codeCours', TextType::class, [
                'label' => 'Code du cours',
            ])
            ->add('titre', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('module', EntityType::class, [
                'class' => Module::class,
                'choice_label' => 'titreModule',
                'label' => 'Module',
                'placeholder' => '-- Selectionner un module --',
            ])
            ->add('niveau', TextType::class, [
                'label' => 'Niveau',
                'required' => false,
            ])
            ->add('credits', IntegerType::class, [
                'label' => 'Credits',
                'required' => false,
            ])
            ->add('langue', TextType::class, [
                'label' => 'Langue',
                'required' => false,
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de debut',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Brouillon' => 'brouillon',
                    'Ouvert' => 'ouvert',
                    'Ferme' => 'ferme',
                    'Archive' => 'archive',
                ],
            ])
            ->add('prerequisIds', TextType::class, [
                'label' => 'Prerequis (IDs de cours, separes par virgule)',
                'mapped' => false,
                'required' => false,
                'data' => (string) ($options['prerequis_data'] ?? ''),
            ]);

        if ($showEnseignants) {
            $builder->add('enseignants', EntityType::class, [
                'class' => Enseignant::class,
                'choice_label' => function (Enseignant $enseignant): string {
                    return $enseignant->getNomComplet() . ' - ' . ($enseignant->getSpecialite() ?? '');
                },
                'placeholder' => 'Aucun',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'attr' => ['class' => 'form-check'],
                'query_builder' => function (EntityRepository $repository) use ($options) {
                    $qb = $repository->createQueryBuilder('e')->orderBy('e.prenom', 'ASC');
                    $filters = $options['enseignant_filters'] ?? null;

                    if ($filters && !empty($filters['search'])) {
                        $query = '%' . trim($filters['search']) . '%';
                        $qb->andWhere("(CONCAT(e.prenom, ' ', e.nom) LIKE :q OR e.nom LIKE :q OR e.prenom LIKE :q)")
                            ->setParameter('q', $query);
                    }

                    if ($filters && !empty($filters['specialite'])) {
                        $qb->andWhere('e.specialite = :spec')
                            ->setParameter('spec', $filters['specialite']);
                    }

                    return $qb;
                },
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cours::class,
            'enseignant_filters' => null,
            'show_enseignants' => true,
            'prerequis_data' => '',
        ]);
    }
}

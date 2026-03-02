<?php

namespace App\Form;

use App\Entity\Message;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Utilisateur $activeUser */
        $activeUser = $options['active_user'];
        $replyMode  = (bool) $options['reply_mode'];

        $builder
            ->add('destinataire', EntityType::class, [
                'class'         => Utilisateur::class,
                'choice_label'  => 'nomComplet', // utilise getNomComplet() de Utilisateur
                'query_builder' => function (UtilisateurRepository $repo) use ($activeUser) {
                    return $repo->createQueryBuilder('u')
                        ->andWhere('u.id != :me')
                        ->setParameter('me', $activeUser->getId())
                        ->orderBy('u.nom', 'ASC');
                },
                'disabled' => $replyMode,
            ])
            ->add('objet', TextType::class, [
                'disabled' => $replyMode,
            ])
            ->add('contenu', TextareaType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'  => Message::class,
            'active_user' => null,
            'reply_mode'  => false,
        ]);
    }
}
<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de commentaire.
 *
 * Il expose uniquement le champ content. L'article et l'auteur sont ajoutes cote
 * serveur dans NewsController pour eviter toute falsification par le navigateur.
 */
class CommentType extends AbstractType
{
    /** Construit le champ textarea envoye par l'utilisateur connecte. */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('content', TextareaType::class, [
            'label' => 'Votre commentaire',
            'attr' => [
                'rows' => 4,
                'placeholder' => 'Écrivez un commentaire constructif...',
            ],
        ]);
    }

    /** Relie le formulaire a l'entite Comment pour remplir Comment::$content. */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}

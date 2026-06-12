<?php

namespace App\Form;

use App\Entity\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire d'administration pour creer ou modifier un article.
 *
 * Un FormType Symfony decrit les champs HTML, leurs labels, leurs options et la
 * classe PHP a remplir. Il ne contient pas la logique d'enregistrement: celle-ci
 * reste dans le controleur.
 */
class ArticleType extends AbstractType
{
    /**
     * Ajoute les champs visibles dans le formulaire.
     *
     * Les noms title, excerpt, content et isPublished correspondent aux proprietes
     * de l'entite Article. Symfony sait donc appeler les setters correspondants
     * quand le formulaire est soumis.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'help' => 'Le slug URL sera généré automatiquement à partir du titre.',
            ])
            ->add('excerpt', TextType::class, [
                'label' => 'Résumé',
                'help' => 'Court texte affiché sur la page d’accueil.',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                // rows controle la hauteur initiale du textarea dans le navigateur.
                'attr' => ['rows' => 12],
            ])
            ->add('isPublished', CheckboxType::class, [
                'label' => 'Publier immédiatement',
                // Une case decochee n'envoie pas toujours de valeur HTML: required
                // false permet donc de garder un brouillon sans erreur formulaire.
                'required' => false,
            ]);
    }

    /**
     * Relie ce formulaire a Article.
     *
     * data_class indique a Symfony quelle entite doit etre remplie par les champs
     * mappes. Sans cette option, le formulaire manipulerait plutot un tableau.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}

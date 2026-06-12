<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Formulaire public d'inscription.
 *
 * Il cree un lecteur simple. Les champs mappes remplissent directement l'entite
 * User, tandis que plainPassword reste un champ temporaire non stocke tel quel.
 */
class RegistrationFormType extends AbstractType
{
    /**
     * Definit les champs demandes a un nouveau lecteur.
     *
     * displayName et email sont mappes sur User. plainPassword est declare avec
     * mapped=false car l'entite ne doit recevoir que le mot de passe hache.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('displayName', TextType::class, [
                'label' => 'Nom affiché',
                // Contrainte classique: le nom public ne peut pas etre vide.
                'constraints' => [
                    new NotBlank(['message' => 'Le nom affiché est obligatoire.']),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'Le nom affiché ne doit pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Choisissez un mot de passe.']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                    ]),
                    // Securite: on interdit explicitement que le nom public soit
                    // identique au mot de passe. Sinon, le mot de passe serait
                    // affiche publiquement sous chaque article et commentaire.
                    new Callback([
                        'callback' => [$this, 'validatePasswordDiffersFromDisplayName'],
                        'groups' => ['Default'],
                    ]),
                ],
            ]);
    }

    /**
     * Verifie que le mot de passe n'est pas identique au nom affiche.
     *
     * Cette regle est placee sur plainPassword car c'est le seul endroit du
     * formulaire ou le mot de passe en clair est encore disponible. Une fois
     * l'entite sauvee, seul le hash reste accessible.
     *
     * @param mixed $plainPassword Valeur du champ plainPassword (peut etre null).
     * @param ExecutionContextInterface $context Contexte de validation Symfony.
     */
    public function validatePasswordDiffersFromDisplayName(mixed $plainPassword, ExecutionContextInterface $context): void
    {
        // Le champ mapped=false n'est pas dans l'entite; on recupere les donnees
        // du formulaire pour comparer le mot de passe au nom affiche choisi.
        $form = $context->getRoot();
        $displayName = (string) $form->get('displayName')->getData();

        if ($displayName !== '' && $displayName === (string) $plainPassword) {
            $context->buildViolation('Le mot de passe ne doit pas être identique au nom affiché, sinon il serait visible publiquement.')
                ->addViolation();
        }
    }

    /** Lie les champs mappes a User; plainPassword reste volontairement separe. */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

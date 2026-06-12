<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Commande pedagogique pour creer ou mettre a jour un utilisateur depuis le terminal.
 *
 * Les commandes Symfony vivent dans src/Command et se lancent avec bin/console.
 * Elles sont utiles pour les taches d'administration, d'import ou de maintenance
 * qui ne doivent pas passer par une page web.
 *
 * Exemple creation administrateur:
 * php bin/console app:create-user admin@example.test SecretAdm1n! "Admin MiniNews" --admin
 *
 * Exemple creation lecteur:
 * php bin/console app:create-user lecteur@example.test MonMotDePasse! "Lecteur MiniNews"
 *
 * Exemple mise a jour volontaire du mot de passe d'un compte existant:
 * php bin/console app:create-user admin@example.test NouveauMotDePasse! "Admin MiniNews" --admin --reset-password
 */
#[AsCommand(name: 'app:create-user', description: 'Crée un utilisateur MiniNews.')]
class CreateUserCommand extends Command
{
    /**
     * Les services necessaires sont injectes par le conteneur Symfony.
     *
     * readonly signifie qu'une fois les proprietes initialisees dans le
     * constructeur, elles ne peuvent plus etre remplacees.
     */
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    /**
     * Decrit les arguments et options acceptes par la commande.
     *
     * Les arguments sont obligatoires et positionnels. L'option --admin ajoute
     * ROLE_ADMIN. L'option --reset-password sert uniquement si l'email existe
     * deja: elle evite de croire qu'un mot de passe a ete change alors que la
     * commande a refuse de recreer le compte.
     */
    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email de connexion')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe en clair, qui sera haché')
            ->addArgument('displayName', InputArgument::REQUIRED, 'Nom affiché publiquement')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Ajoute ROLE_ADMIN')
            ->addOption('reset-password', null, InputOption::VALUE_NONE, 'Met à jour le mot de passe si le compte existe déjà');
    }

    /**
     * Execute la creation ou la mise a jour volontaire de l'utilisateur.
     *
     * Command::SUCCESS vaut 0 pour le terminal. Command::FAILURE indique une
     * erreur, par exemple un email deja utilise sans --reset-password.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // SymfonyStyle offre des messages colores et une meilleure lisibilite.
        $io = new SymfonyStyle($input, $output);

        $email = mb_strtolower((string) $input->getArgument('email'));
        $plainPassword = (string) $input->getArgument('password');
        $displayName = (string) $input->getArgument('displayName');
        $wantsAdmin = (bool) $input->getOption('admin');
        $resetPassword = (bool) $input->getOption('reset-password');

        // Securite: le nom affiche publiquement ne doit jamais etre identique au
        // mot de passe. Sinon, chaque article ou commentaire exposerait le secret
        // de connexion de l'auteur.
        if ($this->displayNameEqualsPassword($displayName, $plainPassword)) {
            $io->error('Le nom affiché ne doit pas être identique au mot de passe : cela exposerait le mot de passe publiquement.');

            return Command::FAILURE;
        }

        $existingUser = $this->users->findOneBy(['email' => $email]);
        if ($existingUser instanceof User) {
            if (!$resetPassword) {
                $output->writeln('<error>Un utilisateur existe déjà avec cet email.</error>');
                $output->writeln('<comment>Le mot de passe n’a pas été modifié. Relancez avec --reset-password pour le remplacer volontairement.</comment>');

                return Command::FAILURE;
            }

            $existingUser->setDisplayName($displayName);
            if ($wantsAdmin) {
                $existingUser->promoteToAdmin();
            }
            $existingUser->setPassword($this->passwordHasher->hashPassword($existingUser, $plainPassword));
            $this->entityManager->flush();

            $output->writeln('<info>Utilisateur existant mis à jour avec succès.</info>');

            return Command::SUCCESS;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setDisplayName($displayName);
        $user->setRoles($wantsAdmin ? ['ROLE_ADMIN'] : ['ROLE_USER']);

        // Le mot de passe arrive en clair depuis le terminal, mais on ne persiste
        // que son hash. C'est la meme regle que dans l'inscription web.
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>Utilisateur créé avec succès.</info>');

        return Command::SUCCESS;
    }

    /**
     * Compare le nom affiche au mot de passe en ignorant la casse et les espaces.
     *
     * Cette comparaison stricte garantit qu'aucune variation obvious (majuscules,
     * espaces en debut/fin) ne permet de contourner la regle de securite.
     */
    private function displayNameEqualsPassword(string $displayName, string $plainPassword): bool
    {
        return mb_strtolower(trim($displayName)) === mb_strtolower(trim($plainPassword));
    }
}

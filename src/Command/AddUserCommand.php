<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use function Symfony\Component\String\u;

#[AsCommand(
    name: 'app:add-user',
    description: 'Creates users and stores them in the database'
)]
class AddUserCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private Validator $validator,
        private UserRepository $users
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'The email of the new user')
            ->addArgument('password', InputArgument::OPTIONAL, 'The plain password of the new user')
            ->addArgument('firstname', InputArgument::OPTIONAL, 'The firstname of the new user')
            ->addArgument('lastname', InputArgument::OPTIONAL, 'The lastname of the new user')
            ->addArgument('manager', InputArgument::OPTIONAL, 'Is the user a manager ?')
        ;
    }

    /**
     * This optional method is the first one executed for a command after configure()
     * and is useful to initialize properties based on the input arguments and options.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * This method is executed after initialize() and before execute(). Its purpose
     * is to check if some of the options/arguments are missing and interactively
     * ask the user for those values.
     *
     * This method is completely optional. If you are developing an internal console
     * command, you probably should not implement this method because it requires
     * quite a lot of work. However, if the command is meant to be used by external
     * users, this method is a nice way to fall back and prevent errors.
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null !== $input->getArgument('email') &&
            null !== $input->getArgument('password') &&
            null !== $input->getArgument('firstname') &&
            null !== $input->getArgument('lastname') &&
            null !== $input->getArgument('manager')) {
            return;
        }

        $this->io->title('Add User Command Interactive Wizard');
        $this->io->text([
            'If you prefer to not use this interactive wizard, provide the',
            'arguments required by this command as follows:',
            '',
            ' $ php bin/console app:add-user username password email@example.com',
            '',
            'Now we\'ll ask you for the value of all the missing command arguments.',
        ]);

        // Ask for the email if it's not defined
        $email = $input->getArgument('email');
        if (null !== $email) {
            $this->io->text(' > <info>Email</info>: '.$email);
        } else {
            $email = $this->io->ask('Email', null, [$this->validator, 'validateEmail']);
            $input->setArgument('email', $email);
        }

        // Ask for the password if it's not defined
        $password = $input->getArgument('password');
        if (null !== $password) {
            $this->io->text(' > <info>Password</info>: '.u('*')->repeat(u($password)->length()));
        } else {
            $password = $this->io->askHidden('Password (your type will be hidden)', [$this->validator, 'validatePassword']);
            $input->setArgument('password', $password);
        }

        // Ask for the firstname if it's not defined
        $firstname = $input->getArgument('firstname');
        if (null !== $firstname) {
            $this->io->text(' > <info>Firstname</info>: '.$firstname);
        } else {
            $firstname = $this->io->ask('Firstname', null);
            $input->setArgument('firstname', $firstname);
        }

        // Ask for the lastname if it's not defined
        $lastname = $input->getArgument('lastname');
        if (null !== $lastname) {
            $this->io->text(' > <info>Lastname</info>: '.$lastname);
        } else {
            $lastname = $this->io->ask('Lastname', null);
            $input->setArgument('lastname', $lastname);
        }

        // Ask if user is manager
        $manager = $input->getArgument('manager');
        if (null !== $manager) {
            $this->io->text(' > <info>Manager ? [y/n]</info>: '.$manager);
        } else {
            $manager = $this->io->ask('Manager ? [y/n]', 'y');
            $input->setArgument('manager', $manager == 'y');
        }
    }

    /**
     * This method is executed after interact() and initialize(). It usually
     * contains the logic to execute to complete this command task.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('add-user-command');

        $plainPassword = $input->getArgument('password');
        $email = $input->getArgument('email');
        $firstName = $input->getArgument('firstname');
        $lastname = $input->getArgument('lastname');
        $isManager = $input->getArgument('manager');

        // make sure to validate the user data is correct
        $this->validateUserData($email, $plainPassword);

        // create the user and hash its password
        $user = new User();
        $user->setFirstname($firstName);
        $user->setLastname($lastname);
        $user->setEmail($email);
        $user->setRoles([$isManager ? 'ROLE_MANAGER' : 'ROLE_VOLUNTEER']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->io->success(sprintf('%s was successfully created: %s', $isManager ? 'Manager user' : 'Volunteer user', $user->getEmail()));

        $event = $stopwatch->stop('add-user-command');
        if ($output->isVerbose()) {
            $this->io->comment(sprintf('New user database id: %d / Elapsed time: %.2f ms / Consumed memory: %.2f MB', $user->getId(), $event->getDuration(), $event->getMemory() / (1024 ** 2)));
        }

        return Command::SUCCESS;
    }

    private function validateUserData($email, $plainPassword): void
    {
        $this->validator->validateEmail($email);

        // first check if a user with the same username already exists.
        $existingUser = $this->users->findOneBy(['email' => $email]);

        if (null !== $existingUser) {
            throw new RuntimeException(sprintf('There is already a user registered with the "%s" email.', $email));
        }

        $this->validator->validatePassword($plainPassword);
    }
}

<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(name: 'app:add-user', description: 'Creates a volunteer or a manager')]
final readonly class AddUserCommand
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('The email of the new user')]
        string $email,
        #[Argument('The firstname of the new user')]
        string $firstname,
        #[Argument('The lastname of the new user')]
        string $lastname,
        #[Argument('The phone of the new user (eg. 06 00 00 00 00)')]
        string $phone,
        #[Option('Create a manager instead of a volunteer')]
        bool $manager = false,
    ): int {
        $user = new User()
            ->setEmail($email)
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setPhone($phone)
            ->setRoles([$manager ? User::ROLE_MANAGER : User::ROLE_VOLUNTEER]);

        $violations = $this->validator->validate($user);
        if (\count($violations) > 0) {
            foreach ($violations as $violation) {
                $io->error(sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage()));
            }

            return Command::FAILURE;
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf(
            '%s %s a été créé·e : %s',
            $manager ? 'Le manager' : 'Le bénévole',
            $user->getFullName(),
            $email,
        ));

        return Command::SUCCESS;
    }
}

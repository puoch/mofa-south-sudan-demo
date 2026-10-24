<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\AuditEvent;
use App\Entity\Mission;
use App\Entity\StaffUser;
use App\Security\FieldCipher;
use App\Security\Totp;
use App\Validator\SyntheticEmailValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Creates a staff account and prints its one-time password and the
 * authenticator-app secret. They are shown once and never stored in plain
 * text. In the prototype, staff email addresses must be synthetic too.
 */
#[AsCommand(name: 'registry:staff:create', description: 'Create a synthetic staff account')]
final class CreateStaffCommand
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly FieldCipher $cipher,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Email address on a test domain, such as hq.officer@example.org')] string $email,
        #[Argument(description: 'mission_staff, hq_assignment, hq_oversight or account_admin')] string $role,
        #[Option(description: 'Mission code, required for mission_staff')] ?string $mission = null,
        #[Option(description: 'Display name')] ?string $name = null,
    ): int {
        $email = strtolower(trim($email));
        $domain = substr(strrchr($email, '@') ?: '', 1);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !SyntheticEmailValidator::isReserved($domain)) {
            $io->error('Use a synthetic email address on a test domain, such as example.org.');

            return 1;
        }
        $roleName = 'ROLE_'.strtoupper($role);
        if (!isset(StaffUser::ROLES[$roleName])) {
            $io->error('Unknown role. Use mission_staff, hq_assignment, hq_oversight or account_admin.');

            return 1;
        }
        $missionEntity = null;
        if ($mission !== null) {
            $missionEntity = $this->entityManager->getRepository(Mission::class)->findOneBy(['code' => $mission]);
            if ($missionEntity === null) {
                $io->error('Unknown mission code '.$mission.'.');

                return 1;
            }
        }

        $secret = Totp::generateSecret();
        $password = rtrim(strtr(base64_encode(random_bytes(15)), '+/', '-_'), '=');
        try {
            $user = new StaffUser($email, $name ?? $email, $roleName, $this->cipher->encrypt($secret, 'totp_secret'), $missionEntity);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return 1;
        }
        $user->setPasswordHash($this->hasher->hashPassword($user, $password));
        $this->entityManager->persist($user);
        $this->entityManager->persist(new AuditEvent('cli', 'account.created', substr('staff:'.$email, 0, 64)));
        $this->entityManager->flush();

        $io->success('Created '.$email.' ('.$user->getRoleLabel().').');
        $io->definitionList(
            ['Password' => $password],
            ['Authenticator secret' => $secret],
            ['Authenticator URI' => Totp::provisioningUri($secret, $email)],
        );
        $io->note('These are shown once. Add the secret to an authenticator app now.');

        return 0;
    }
}

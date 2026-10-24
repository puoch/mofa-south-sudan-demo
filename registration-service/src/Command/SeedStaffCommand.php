<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Mission;
use App\Entity\StaffUser;
use App\Security\FieldCipher;
use App\Security\Totp;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Creates one demonstration account per role, for demos and browser tests.
 *
 * The password and authenticator secret come from REGISTRY_DEMO_PASSWORD and
 * REGISTRY_DEMO_TOTP_SECRET, so no credentials are committed. Each --set
 * adds a group of accounts (mission.a.SET@example.org and so on), because an
 * authenticator code can be used only once per account.
 */
#[AsCommand(name: 'registry:seed-staff', description: 'Create demonstration staff accounts from environment variables')]
final class SeedStaffCommand
{
    private const ACCOUNTS = [
        'mission.a' => [StaffUser::ROLE_MISSION_STAFF, 'DEMO-A', 'Demo mission officer A'],
        'mission.b' => [StaffUser::ROLE_MISSION_STAFF, 'DEMO-B', 'Demo mission officer B'],
        'hq.assignment' => [StaffUser::ROLE_HQ_ASSIGNMENT, null, 'Demo HQ assignment officer'],
        'hq.oversight' => [StaffUser::ROLE_HQ_OVERSIGHT, null, 'Demo HQ oversight officer'],
        'accounts' => [StaffUser::ROLE_ACCOUNT_ADMIN, null, 'Demo account administrator'],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly FieldCipher $cipher,
    ) {
    }

    /**
     * @param string[] $set
     */
    public function __invoke(SymfonyStyle $io, #[Option(description: 'Account group name, repeatable')] array $set = ['demo']): int
    {
        $password = (string) ($_SERVER['REGISTRY_DEMO_PASSWORD'] ?? getenv('REGISTRY_DEMO_PASSWORD'));
        $secret = strtoupper((string) ($_SERVER['REGISTRY_DEMO_TOTP_SECRET'] ?? getenv('REGISTRY_DEMO_TOTP_SECRET')));
        if (strlen($password) < 12 || !preg_match('/^[A-Z2-7]{16,}$/', $secret)) {
            $io->error('Set REGISTRY_DEMO_PASSWORD (12+ characters) and REGISTRY_DEMO_TOTP_SECRET (base32, for example from Totp::generateSecret()).');

            return 1;
        }

        $missions = $this->entityManager->getRepository(Mission::class);
        $users = $this->entityManager->getRepository(StaffUser::class);
        $created = 0;
        foreach ($set as $group) {
            if (!preg_match('/^[a-z0-9]{1,16}$/', $group)) {
                $io->error('Set names use lowercase letters and digits only.');

                return 1;
            }
            foreach (self::ACCOUNTS as $prefix => [$role, $missionCode, $name]) {
                $email = $prefix.'.'.$group.'@example.org';
                if ($users->findOneBy(['email' => $email])) {
                    continue;
                }
                $mission = $missionCode ? $missions->findOneBy(['code' => $missionCode]) : null;
                if ($missionCode && !$mission) {
                    $io->error('Run registry:seed-missions first.');

                    return 1;
                }
                $user = new StaffUser($email, $name, $role, $this->cipher->encrypt($secret, 'totp_secret'), $mission);
                $user->setPasswordHash($this->hasher->hashPassword($user, $password));
                $this->entityManager->persist($user);
                ++$created;
            }
        }
        $this->entityManager->flush();
        $io->success(sprintf('Created %d demonstration staff accounts.', $created));

        return 0;
    }
}

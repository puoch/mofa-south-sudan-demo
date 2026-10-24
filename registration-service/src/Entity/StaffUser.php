<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A Ministry or mission staff account for the staff console.
 *
 * Each account has exactly one role. Mission staff are bound to one mission.
 * The authenticator-app secret is stored encrypted. Accounts are deactivated,
 * never deleted, so the audit log keeps its meaning.
 */
#[ORM\Entity]
#[ORM\Table(name: 'staff_user')]
#[ORM\UniqueConstraint(name: 'staff_user_email_unique', columns: ['email'])]
class StaffUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_MISSION_STAFF = 'ROLE_MISSION_STAFF';
    public const ROLE_HQ_ASSIGNMENT = 'ROLE_HQ_ASSIGNMENT';
    public const ROLE_HQ_OVERSIGHT = 'ROLE_HQ_OVERSIGHT';
    public const ROLE_ACCOUNT_ADMIN = 'ROLE_ACCOUNT_ADMIN';

    public const ROLES = [
        self::ROLE_MISSION_STAFF => 'Mission staff',
        self::ROLE_HQ_ASSIGNMENT => 'HQ assignment officer',
        self::ROLE_HQ_OVERSIGHT => 'HQ oversight officer',
        self::ROLE_ACCOUNT_ADMIN => 'Account administrator',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $passwordHash = '';

    #[ORM\Column]
    private bool $active = true;

    /** Last accepted authenticator time step, to stop a code being reused. */
    #[ORM\Column(type: Types::BIGINT, options: ['default' => 0])]
    private string $lastTotpStep = '0';

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    public function __construct(
        #[ORM\Column(length: 254)]
        private string $email,
        #[ORM\Column(length: 120)]
        private string $displayName,
        #[ORM\Column(length: 32)]
        private string $role,
        #[ORM\Column(type: Types::TEXT)]
        private string $totpSecret,
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: true)]
        private ?Mission $mission = null,
    ) {
        if (!isset(self::ROLES[$role])) {
            throw new \InvalidArgumentException('Unknown staff role '.$role.'.');
        }
        if (($role === self::ROLE_MISSION_STAFF) !== ($mission !== null)) {
            throw new \InvalidArgumentException('Mission staff need exactly one mission; other roles have none.');
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getRoles(): array
    {
        return [$this->role, 'ROLE_STAFF'];
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getRoleLabel(): string
    {
        return self::ROLES[$this->role];
    }

    public function getMission(): ?Mission
    {
        return $this->mission;
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $hash): void
    {
        $this->passwordHash = $hash;
    }

    public function getTotpSecret(): string
    {
        return $this->totpSecret;
    }

    public function getLastTotpStep(): int
    {
        return (int) $this->lastTotpStep;
    }

    public function recordLogin(int $totpStep): void
    {
        $this->lastTotpStep = (string) $totpStep;
        $this->lastLoginAt = new \DateTimeImmutable();
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function eraseCredentials(): void
    {
    }
}

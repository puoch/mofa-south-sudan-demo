<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A diaspora registration. Contact and residence details only: passport
 * details and date of birth live encrypted in RegistrationSensitive.
 *
 * Every row is synthetic. data_class is fixed to "synthetic" by a database
 * check constraint, so this schema cannot hold a record marked as real.
 */
#[ORM\Entity]
#[ORM\Table(name: 'registration')]
#[ORM\UniqueConstraint(name: 'registration_reference_unique', columns: ['reference'])]
class Registration
{
    /** Records wait in the headquarters queue until routing rules are approved. */
    public const ASSIGNMENT_HQ_QUEUE = 'hq_queue';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 23)]
    private string $reference;

    #[ORM\Column(length: 200)]
    private string $fullName;

    #[ORM\Column(length: 254)]
    private string $email;

    #[ORM\Column(length: 16)]
    private string $phone;

    #[ORM\Column(length: 2)]
    private string $nationality;

    #[ORM\Column(length: 2)]
    private string $countryOfResidence;

    #[ORM\Column(length: 100)]
    private string $cityOfResidence;

    #[ORM\Column(length: 32)]
    private string $assignment = self::ASSIGNMENT_HQ_QUEUE;

    #[ORM\Column(length: 32)]
    private string $status = 'submitted';

    #[ORM\Column]
    private bool $possibleDuplicate = false;

    #[ORM\Column(length: 16, options: ['default' => 'synthetic'])]
    private string $dataClass = 'synthetic';

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToOne(mappedBy: 'registration', cascade: ['persist', 'remove'])]
    private ?RegistrationSensitive $sensitive = null;

    public function __construct(string $reference, string $fullName, string $email, string $phone, string $nationality, string $countryOfResidence, string $cityOfResidence)
    {
        $this->reference = $reference;
        $this->fullName = $fullName;
        $this->email = $email;
        $this->phone = $phone;
        $this->nationality = $nationality;
        $this->countryOfResidence = $countryOfResidence;
        $this->cityOfResidence = $cityOfResidence;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getAssignment(): string
    {
        return $this->assignment;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isPossibleDuplicate(): bool
    {
        return $this->possibleDuplicate;
    }

    public function flagPossibleDuplicate(): void
    {
        $this->possibleDuplicate = true;
    }

    public function getDataClass(): string
    {
        return $this->dataClass;
    }

    public function getSensitive(): ?RegistrationSensitive
    {
        return $this->sensitive;
    }

    public function setSensitive(RegistrationSensitive $sensitive): void
    {
        $this->sensitive = $sensitive;
    }
}

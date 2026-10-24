<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Encrypted passport details and date of birth for one registration.
 * Passport columns are empty when the registrant gave no passport.
 *
 * Kept in its own table so that, in a later phase, database grants and
 * row-level security can give staff roles access to contact details without
 * the encrypted columns. Columns hold FieldCipher output, never plain text.
 */
#[ORM\Entity]
#[ORM\Table(name: 'registration_sensitive')]
#[ORM\Index(name: 'registration_sensitive_passport_index', columns: ['passport_index'])]
class RegistrationSensitive
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'sensitive')]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private Registration $registration;

    #[ORM\Column(type: Types::TEXT)]
    private string $dateOfBirth;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $passportNumber;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $passportIssuingCountry;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $passportExpiry;

    /** Keyed hash of the normalized passport number, for duplicate checks. */
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $passportIndex;

    public function __construct(Registration $registration, string $dateOfBirth, ?string $passportNumber = null, ?string $passportIssuingCountry = null, ?string $passportExpiry = null, ?string $passportIndex = null)
    {
        $this->registration = $registration;
        $this->dateOfBirth = $dateOfBirth;
        $this->passportNumber = $passportNumber;
        $this->passportIssuingCountry = $passportIssuingCountry;
        $this->passportExpiry = $passportExpiry;
        $this->passportIndex = $passportIndex;
        $registration->setSensitive($this);
    }

    public function getDateOfBirth(): string
    {
        return $this->dateOfBirth;
    }

    public function getPassportNumber(): ?string
    {
        return $this->passportNumber;
    }

    public function getPassportIssuingCountry(): ?string
    {
        return $this->passportIssuingCountry;
    }

    public function getPassportExpiry(): ?string
    {
        return $this->passportExpiry;
    }

    public function getPassportIndex(): ?string
    {
        return $this->passportIndex;
    }
}

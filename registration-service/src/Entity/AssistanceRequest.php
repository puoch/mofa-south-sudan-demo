<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A distress call or enquiry from a member of the diaspora.
 *
 * The description and location details are stored encrypted. Every request
 * is routed to headquarters (a database check enforces it); the mission
 * column holds the code of the mission that also receives it, when the
 * jurisdiction rules name one for the country. Every row is synthetic, as for registrations.
 */
#[ORM\Entity]
#[ORM\Table(name: 'assistance_request')]
#[ORM\UniqueConstraint(name: 'assistance_request_reference_unique', columns: ['reference'])]
class AssistanceRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 16)]
    private string $status = 'submitted';

    #[ORM\Column]
    private bool $routedToHeadquarters = true;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $missionAssignment = null;

    #[ORM\Column(length: 16, options: ['default' => 'synthetic'])]
    private string $dataClass = 'synthetic';

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        #[ORM\Column(length: 23)]
        private string $reference,
        #[ORM\Column(length: 16)]
        private string $requestType,
        #[ORM\Column(length: 200)]
        private string $fullName,
        #[ORM\Column(length: 254)]
        private string $email,
        #[ORM\Column(length: 16)]
        private string $phone,
        #[ORM\Column(length: 2)]
        private string $country,
        #[ORM\Column(length: 100)]
        private string $city,
        #[ORM\Column(type: Types::TEXT)]
        private string $message,
        #[ORM\Column(type: Types::TEXT, nullable: true)]
        private ?string $locationDetails,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function assignMission(string $missionId): void
    {
        $this->missionAssignment = $missionId;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getRequestType(): string
    {
        return $this->requestType;
    }

    public function getMissionAssignment(): ?string
    {
        return $this->missionAssignment;
    }

    public function isRoutedToHeadquarters(): bool
    {
        return $this->routedToHeadquarters;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLocationDetails(): ?string
    {
        return $this->locationDetails;
    }
}

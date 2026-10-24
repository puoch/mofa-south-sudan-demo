<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Says which mission serves registrants living in a country.
 *
 * "resident" means the mission is in that country; "non_resident" means a
 * mission elsewhere covers it. In the prototype every rule is synthetic and
 * is not a statement of any real diplomatic jurisdiction.
 */
#[ORM\Entity]
#[ORM\Table(name: 'jurisdiction_rule')]
#[ORM\Index(name: 'jurisdiction_rule_country', columns: ['country_code'])]
class JurisdictionRule
{
    public const TYPE_RESIDENT = 'resident';
    public const TYPE_NON_RESIDENT = 'non_resident';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $approvedBy = null;

    public function __construct(
        #[ORM\Column(length: 2)]
        private string $countryCode,
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false)]
        private Mission $mission,
        #[ORM\Column(length: 16)]
        private string $type = self::TYPE_RESIDENT,
    ) {
    }

    public function approve(string $by): void
    {
        $this->approvedBy = $by;
        $this->approvedAt = new \DateTimeImmutable();
    }

    public function isApproved(): bool
    {
        return $this->approvedAt !== null;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getMission(): Mission
    {
        return $this->mission;
    }

    public function getType(): string
    {
        return $this->type;
    }
}

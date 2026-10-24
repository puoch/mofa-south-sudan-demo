<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A diplomatic mission that records can be assigned to.
 *
 * Every mission in the prototype is synthetic ("Demonstration Mission A")
 * and represents no real embassy or consulate. Only approved missions take
 * part in routing; draft entries never receive records.
 */
#[ORM\Entity]
#[ORM\Table(name: 'mission')]
#[ORM\UniqueConstraint(name: 'mission_code_unique', columns: ['code'])]
class Mission
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 32)]
        private string $code,
        #[ORM\Column(length: 120)]
        private string $name,
        #[ORM\Column(length: 16)]
        private string $status = self::STATUS_DRAFT,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}

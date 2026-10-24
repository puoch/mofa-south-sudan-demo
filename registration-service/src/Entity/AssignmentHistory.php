<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One change of a record's assignment. Append-only, like the audit log.
 */
#[ORM\Entity]
#[ORM\Table(name: 'assignment_history')]
class AssignmentHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $changedAt;

    public function __construct(
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Registration $registration,
        #[ORM\Column(length: 32)]
        private string $fromAssignment,
        #[ORM\Column(length: 32)]
        private string $toAssignment,
        #[ORM\Column(length: 64)]
        private string $actor,
        #[ORM\Column(length: 300)]
        private string $reason,
    ) {
        $this->changedAt = new \DateTimeImmutable();
    }

    public function getFromAssignment(): string
    {
        return $this->fromAssignment;
    }

    public function getToAssignment(): string
    {
        return $this->toAssignment;
    }

    public function getActor(): string
    {
        return $this->actor;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getChangedAt(): \DateTimeImmutable
    {
        return $this->changedAt;
    }
}

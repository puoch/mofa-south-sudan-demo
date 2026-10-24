<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One entry in the audit log. Entries are only ever inserted: the class has
 * no setters, and a database trigger rejects UPDATE and DELETE.
 *
 * The target is a registration reference, never a name or passport detail.
 */
#[ORM\Entity]
#[ORM\Table(name: 'audit_event')]
class AuditEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $occurredAt;

    public function __construct(
        #[ORM\Column(length: 64)]
        private string $actor,
        #[ORM\Column(length: 64)]
        private string $action,
        #[ORM\Column(length: 64, nullable: true)]
        private ?string $target,
        #[ORM\Column(length: 16)]
        private string $outcome = 'success',
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }
}

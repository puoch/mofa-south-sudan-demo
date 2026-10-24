<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Deletes every registration and assistance request from a development
 * database.
 *
 * The audit log is kept, because it is append-only by design.
 */
#[AsCommand(name: 'registry:purge', description: 'Delete all synthetic registrations and assistance requests from this development database')]
final class PurgeCommand
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function __invoke(SymfonyStyle $io, #[Option(description: 'Skip the confirmation question')] bool $force = false): int
    {
        if (!$force && !$io->confirm('Delete every registration and assistance request in this database?', false)) {
            return 0;
        }
        $deleted = $this->connection->executeStatement('DELETE FROM registration');
        $requests = $this->connection->executeStatement('DELETE FROM assistance_request');
        $io->success(sprintf('Deleted %d registrations and %d assistance requests.', $deleted, $requests));

        return 0;
    }
}

<?php

declare(strict_types=1);

namespace App\Command;

use App\Registration\Registrar;
use App\Registration\RegistrationRequest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Creates made-up registrations for development and demonstrations.
 *
 * Names are invented combinations, emails use example.org, phone numbers use
 * the +1 555-01xx range reserved for fiction, and passport numbers use the
 * SYN prefix. None of it describes a real person.
 */
#[AsCommand(name: 'registry:seed-synthetic', description: 'Create synthetic registrations for development')]
final class SeedSyntheticCommand
{
    private const GIVEN = ['Amani', 'Deng', 'Nyakuoth', 'Lual', 'Achol', 'Garang', 'Nyandeng', 'Malual', 'Abuk', 'Kuol'];
    private const FAMILY = ['Example', 'Sample', 'Testcase', 'Placeholder', 'Demo'];
    private const COUNTRIES = ['KE', 'UG', 'US', 'GB', 'AU', 'CA', 'EG', 'ET', 'DE', 'AE'];

    public function __construct(private readonly Registrar $registrar)
    {
    }

    public function __invoke(SymfonyStyle $io, #[Option(description: 'How many registrations to create')] int $count = 20): int
    {
        for ($i = 0; $i < $count; ++$i) {
            $given = self::GIVEN[random_int(0, count(self::GIVEN) - 1)];
            $family = self::FAMILY[random_int(0, count(self::FAMILY) - 1)];
            $request = new RegistrationRequest();
            $request->fullName = $given.' '.$family;
            $request->email = strtolower($given.'.'.$family.random_int(1, 999)).'@example.org';
            $request->phone = '+1555010'.str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT);
            $request->nationality = 'SS';
            $request->countryOfResidence = self::COUNTRIES[random_int(0, count(self::COUNTRIES) - 1)];
            $request->cityOfResidence = 'Sample City';
            $request->dateOfBirth = new \DateTimeImmutable(sprintf('%d-%02d-%02d', random_int(1950, 2005), random_int(1, 12), random_int(1, 28)));
            $request->passportNumber = sprintf('SYN%06d', random_int(0, 999999));
            $request->passportIssuingCountry = 'SS';
            $request->passportExpiry = new \DateTimeImmutable(sprintf('%d-%02d-%02d', random_int(2027, 2035), random_int(1, 12), random_int(1, 28)));
            $request->syntheticConfirmation = true;
            $request->privacyAcknowledged = true;
            $this->registrar->register($request, 'seed_command');
        }
        $io->success(sprintf('Created %d synthetic registrations.', $count));

        return 0;
    }
}

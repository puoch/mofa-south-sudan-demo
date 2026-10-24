<?php

declare(strict_types=1);

namespace App\Registration;

use App\Entity\AuditEvent;
use App\Entity\Registration;
use App\Entity\RegistrationSensitive;
use App\Security\FieldCipher;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Stores a validated registration and returns its reference number.
 *
 * Every record goes to the headquarters queue: no jurisdiction rules are
 * approved, so nothing is routed to a mission. A matching passport or
 * national ID number is flagged for headquarters as a possible duplicate, never merged or rejected,
 * because the Ministry has not decided how duplicates are handled.
 */
final class Registrar
{
    private const MAX_REFERENCE_ATTEMPTS = 3;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FieldCipher $cipher,
        private readonly ReferenceGenerator $references,
    ) {
    }

    public function register(RegistrationRequest $request, string $actor = 'public_form'): string
    {
        $passport = $request->hasPassport() ? strtoupper(trim((string) $request->passportNumber)) : null;
        if ($passport !== null && !preg_match(RegistrationRequest::SYNTHETIC_PASSPORT_PATTERN, $passport)) {
            throw new \DomainException('Only synthetic passport numbers can be stored.');
        }
        $passportIndex = $passport !== null ? $this->cipher->blindIndex($passport, 'passport_number') : null;
        $nationalId = $request->hasNationalId() ? strtoupper(trim((string) $request->nationalIdNumber)) : null;
        if ($nationalId !== null && !preg_match(RegistrationRequest::SYNTHETIC_NATIONAL_ID_PATTERN, $nationalId)) {
            throw new \DomainException('Only synthetic national ID numbers can be stored.');
        }
        $nationalIdIndex = $nationalId !== null ? $this->cipher->blindIndex($nationalId, 'national_id_number') : null;
        $sensitiveRepository = $this->entityManager->getRepository(RegistrationSensitive::class);
        $isDuplicate = ($passportIndex !== null && $sensitiveRepository->count(['passportIndex' => $passportIndex]) > 0)
            || ($nationalIdIndex !== null && $sensitiveRepository->count(['nationalIdIndex' => $nationalIdIndex]) > 0);

        $registration = new Registration(
            $this->uniqueReference(),
            trim((string) $request->fullName),
            strtolower(trim((string) $request->email)),
            (string) $request->phone,
            (string) $request->nationality,
            (string) $request->countryOfResidence,
            trim((string) $request->cityOfResidence),
        );
        if ($isDuplicate) {
            $registration->flagPossibleDuplicate();
        }
        $sensitive = new RegistrationSensitive(
            $registration,
            $this->cipher->encrypt($request->dateOfBirth->format('Y-m-d'), 'date_of_birth'),
            $passport !== null ? $this->cipher->encrypt($passport, 'passport_number') : null,
            $passport !== null ? $this->cipher->encrypt((string) $request->passportIssuingCountry, 'passport_issuing_country') : null,
            $passport !== null ? $this->cipher->encrypt($request->passportExpiry->format('Y-m-d'), 'passport_expiry') : null,
            $passportIndex,
        );
        if ($nationalId !== null) {
            $sensitive->setNationalId($this->cipher->encrypt($nationalId, 'national_id_number'), $nationalIdIndex);
        }
        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $em) use ($registration, $actor): void {
            $em->persist($registration);
            $em->persist(new AuditEvent($actor, 'registration.created', $registration->getReference()));
        });

        return $registration->getReference();
    }

    /**
     * Returns a reference not yet in use. With 75 random bits a collision is
     * vanishingly unlikely; the unique constraint is the final guard.
     */
    private function uniqueReference(): string
    {
        $repository = $this->entityManager->getRepository(Registration::class);
        for ($attempt = 0; $attempt < self::MAX_REFERENCE_ATTEMPTS; ++$attempt) {
            $reference = $this->references->generate();
            if ($repository->count(['reference' => $reference]) === 0) {
                return $reference;
            }
        }

        throw new \RuntimeException('Could not generate an unused reference number.');
    }
}

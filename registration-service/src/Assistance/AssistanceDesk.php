<?php

declare(strict_types=1);

namespace App\Assistance;

use App\Entity\AssistanceRequest;
use App\Entity\AuditEvent;
use App\Entity\Mission;
use App\Entity\Registration;
use App\Registration\MissionRouter;
use App\Registration\ReferenceGenerator;
use App\Security\FieldCipher;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Stores a distress call or enquiry and routes it.
 *
 * Headquarters always receives the request. The mission that serves the
 * country the person is in receives it too, chosen by the same MissionRouter
 * and jurisdiction rules as registrations; when no single approved mission
 * serves the country, headquarters decides who responds.
 *
 * Nothing is sent: no email or SMS provider is approved, so the routing is
 * recorded in the audit log and the application log, and shown on the
 * confirmation page. Neither log holds personal details, only the reference.
 */
final class AssistanceDesk
{
    private const MAX_REFERENCE_ATTEMPTS = 3;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FieldCipher $cipher,
        private readonly ReferenceGenerator $references,
        private readonly MissionRouter $router,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{reference: string, mission: string|null}
     *   The reference number, and the name of the mission that also
     *   receives the request, if any.
     */
    public function submit(AssistanceSubmission $submission, string $actor = 'public_form'): array
    {
        $country = (string) $submission->country;
        $assignment = $this->router->route($country);
        $mission = $assignment === Registration::ASSIGNMENT_HQ_QUEUE ? null : $this->entityManager->getRepository(Mission::class)->findOneBy(['code' => $assignment]);

        $request = new AssistanceRequest(
            $this->uniqueReference(),
            (string) $submission->requestType,
            trim((string) $submission->fullName),
            strtolower(trim((string) $submission->email)),
            (string) $submission->phone,
            $country,
            trim((string) $submission->city),
            $this->cipher->encrypt(trim((string) $submission->message), 'assistance_message'),
            $submission->hasLocationDetails() ? $this->cipher->encrypt(trim((string) $submission->locationDetails), 'assistance_location_details') : null,
        );
        if ($mission !== null) {
            $request->assignMission($mission->getCode());
        }

        $reference = $request->getReference();
        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $em) use ($request, $mission, $actor, $reference): void {
            $em->persist($request);
            $em->persist(new AuditEvent($actor, 'assistance.created', $reference));
            $em->persist(new AuditEvent('router', 'assistance.routed_headquarters', $reference));
            $em->persist(new AuditEvent('router', $mission !== null ? 'assistance.routed_mission' : 'assistance.mission_unassigned', $reference));
        });

        $this->logger->notice('Assistance request {reference} ({type}) routed; no notification sent in the prototype.', [
            'reference' => $reference,
            'type' => $request->getRequestType(),
            'headquarters' => true,
            'mission' => $mission?->getCode(),
        ]);

        return ['reference' => $reference, 'mission' => $mission?->getName()];
    }

    private function uniqueReference(): string
    {
        $repository = $this->entityManager->getRepository(AssistanceRequest::class);
        for ($attempt = 0; $attempt < self::MAX_REFERENCE_ATTEMPTS; ++$attempt) {
            $reference = $this->references->generate();
            if ($repository->count(['reference' => $reference]) === 0) {
                return $reference;
            }
        }

        throw new \RuntimeException('Could not generate an unused reference number.');
    }
}

<?php

declare(strict_types=1);

namespace App\Registration;

use App\Entity\JurisdictionRule;
use App\Entity\Registration;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Decides which mission a new record goes to, following the specification:
 *
 * 1. Exactly one approved resident mission for the country: that mission.
 * 2. Otherwise exactly one approved non-resident mission: that mission.
 * 3. Anything else (no rule, several missions, unapproved rules or missions):
 *    the headquarters queue, for an HQ assignment officer to decide.
 */
final class MissionRouter
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function route(string $countryOfResidence): string
    {
        /** @var JurisdictionRule[] $rules */
        $rules = $this->entityManager->getRepository(JurisdictionRule::class)->findBy(['countryCode' => $countryOfResidence]);
        $byType = [JurisdictionRule::TYPE_RESIDENT => [], JurisdictionRule::TYPE_NON_RESIDENT => []];
        foreach ($rules as $rule) {
            if ($rule->isApproved() && $rule->getMission()->isApproved()) {
                $byType[$rule->getType()][$rule->getMission()->getCode()] = true;
            }
        }
        foreach ([JurisdictionRule::TYPE_RESIDENT, JurisdictionRule::TYPE_NON_RESIDENT] as $type) {
            if (count($byType[$type]) === 1) {
                return array_key_first($byType[$type]);
            }
            if (count($byType[$type]) > 1) {
                return Registration::ASSIGNMENT_HQ_QUEUE;
            }
        }

        return Registration::ASSIGNMENT_HQ_QUEUE;
    }
}

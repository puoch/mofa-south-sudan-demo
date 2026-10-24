<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\JurisdictionRule;
use App\Entity\Mission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Creates synthetic missions and routing rules for development.
 *
 * The missions are fictional ("Demonstration Mission A") and the rules are
 * not statements of any real diplomatic jurisdiction. Mission C stays in
 * draft and two rules overlap, so every routing outcome can be tried.
 */
#[AsCommand(name: 'registry:seed-missions', description: 'Create synthetic missions and routing rules for development')]
final class SeedMissionsCommand
{
    private const MISSIONS = [
        'DEMO-A' => ['Demonstration Mission A (synthetic)', Mission::STATUS_APPROVED],
        'DEMO-B' => ['Demonstration Mission B (synthetic)', Mission::STATUS_APPROVED],
        'DEMO-C' => ['Demonstration Mission C (synthetic, draft)', Mission::STATUS_DRAFT],
    ];

    /** [country, mission, type, approved] */
    private const RULES = [
        ['KE', 'DEMO-A', JurisdictionRule::TYPE_RESIDENT, true],
        ['ET', 'DEMO-A', JurisdictionRule::TYPE_NON_RESIDENT, true],
        ['UG', 'DEMO-B', JurisdictionRule::TYPE_RESIDENT, true],
        ['US', 'DEMO-B', JurisdictionRule::TYPE_RESIDENT, false],
        ['EG', 'DEMO-C', JurisdictionRule::TYPE_RESIDENT, true],
        ['GB', 'DEMO-A', JurisdictionRule::TYPE_NON_RESIDENT, true],
        ['GB', 'DEMO-B', JurisdictionRule::TYPE_NON_RESIDENT, true],
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $repository = $this->entityManager->getRepository(Mission::class);
        $missions = [];
        foreach (self::MISSIONS as $code => [$name, $status]) {
            $mission = $repository->findOneBy(['code' => $code]) ?? new Mission($code, $name);
            $mission->setStatus($status);
            $this->entityManager->persist($mission);
            $missions[$code] = $mission;
        }
        $this->entityManager->flush();

        $this->entityManager->createQuery('DELETE FROM App\Entity\JurisdictionRule r')->execute();
        foreach (self::RULES as [$country, $code, $type, $approved]) {
            $rule = new JurisdictionRule($country, $missions[$code], $type);
            if ($approved) {
                $rule->approve('seed_command');
            }
            $this->entityManager->persist($rule);
        }
        $this->entityManager->flush();
        $io->success(sprintf('Created %d synthetic missions and %d routing rules.', count(self::MISSIONS), count(self::RULES)));

        return 0;
    }
}

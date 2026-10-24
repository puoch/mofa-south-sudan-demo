<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\JurisdictionRule;
use App\Entity\Mission;
use App\Registration\ReferenceGenerator;
use App\Security\FieldCipher;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AssistanceFormTest extends WebTestCase
{
    private KernelBrowser $client;
    private Connection $db;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->db = static::getContainer()->get(Connection::class);
        $this->db->executeStatement('DELETE FROM assistance_request');
        // No routing rules unless a test adds them.
        $this->db->executeStatement('DELETE FROM jurisdiction_rule');
    }

    /**
     * Adds a synthetic mission serving one country, as registry:seed-missions does.
     */
    private function addSyntheticMission(string $country, bool $approved = true): void
    {
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $mission = $em->getRepository(Mission::class)->findOneBy(['code' => 'DEMO-A']) ?? new Mission('DEMO-A', 'Demonstration Mission A (synthetic)');
        $mission->setStatus(Mission::STATUS_APPROVED);
        $em->persist($mission);
        $rule = new JurisdictionRule($country, $mission);
        if ($approved) {
            $rule->approve('test');
        }
        $em->persist($rule);
        $em->flush();
    }

    private function validData(array $overrides = []): array
    {
        return $overrides + [
            'assistance[requestType]' => 'distress',
            'assistance[fullName]' => 'Amani Example',
            'assistance[email]' => 'amani@example.org',
            'assistance[phone]' => '+15550100123',
            'assistance[message]' => 'Synthetic test: my made-up passport was lost and I need advice.',
            'assistance[country]' => 'KE',
            'assistance[city]' => 'Sample City',
            'assistance[locationDetails]' => 'Near the made-up central bus station',
            'assistance[syntheticConfirmation]' => '1',
            'assistance[notEmergencyAcknowledged]' => '1',
        ];
    }

    private function submit(array $data): void
    {
        $crawler = $this->client->request('GET', '/assistance');
        $form = $crawler->selectButton('Submit request')->form();
        foreach (['assistance[syntheticConfirmation]', 'assistance[notEmergencyAcknowledged]'] as $box) {
            if (empty($data[$box])) {
                $form[$box]->untick();
                unset($data[$box]);
            }
        }
        // A radio group cannot be cleared, only left out.
        if (array_key_exists('assistance[requestType]', $data) && $data['assistance[requestType]'] === null) {
            $form->remove('assistance[requestType]');
            unset($data['assistance[requestType]']);
        }
        $this->client->submit($form, $data);
    }

    /** @return array<string, mixed> */
    private function onlyRow(): array
    {
        $rows = $this->db->fetchAllAssociative('SELECT * FROM assistance_request');
        self::assertCount(1, $rows);

        return $rows[0];
    }

    public function testPageSaysItIsNotAnEmergencyService(): void
    {
        $this->client->request('GET', '/assistance');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[aria-label="This is not an emergency service"]', 'If you are in immediate danger, contact the local emergency services');
        self::assertSelectorTextContains('.demo-banner', 'Demonstration only');
        self::assertResponseHeaderSame('X-Robots-Tag', 'noindex, nofollow');
        self::assertStringContainsString('geolocation=()', (string) $this->client->getResponse()->headers->get('Permissions-Policy'));
    }

    public function testValidRequestIsEncryptedAndRoutedToHeadquartersOnly(): void
    {
        $this->submit($this->validData());
        self::assertResponseRedirects('/assistance/confirmation', 303);
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        $reference = trim($this->client->getCrawler()->filter('.reference')->text());
        self::assertTrue(ReferenceGenerator::isValid($reference));
        self::assertSelectorTextContains('.routing', 'Received (every request goes to headquarters)');
        self::assertSelectorTextContains('.routing', 'no single approved mission serves this country');
        self::assertSelectorTextContains('main', 'No one will respond to this request.');

        $row = $this->onlyRow();
        self::assertSame($reference, $row['reference']);
        self::assertSame('synthetic', $row['data_class']);
        self::assertSame('distress', $row['request_type']);
        self::assertTrue($row['routed_to_headquarters']);
        self::assertNull($row['mission_assignment']);
        self::assertStringStartsWith('v1:', $row['message']);
        self::assertStringNotContainsString('passport', $row['message']);
        $cipher = static::getContainer()->get(FieldCipher::class);
        self::assertSame('Synthetic test: my made-up passport was lost and I need advice.', $cipher->decrypt($row['message'], 'assistance_message'));
        self::assertSame('Near the made-up central bus station', $cipher->decrypt($row['location_details'], 'assistance_location_details'));

        $actions = $this->db->fetchFirstColumn('SELECT action FROM audit_event WHERE target = ? ORDER BY id', [$reference]);
        self::assertSame(['assistance.created', 'assistance.routed_headquarters', 'assistance.mission_unassigned'], $actions);

        // Shown once only.
        $this->client->request('GET', '/assistance/confirmation');
        self::assertResponseRedirects('/assistance');
    }

    public function testRequestIsAlsoRoutedToTheMissionServingTheCountry(): void
    {
        $this->addSyntheticMission('KE');
        $this->submit($this->validData(['assistance[requestType]' => 'enquiry', 'assistance[locationDetails]' => '']));
        $this->client->followRedirect();
        self::assertSelectorTextContains('.routing', 'Received by Demonstration Mission A (synthetic)');
        self::assertSelectorTextContains('.routing', 'Received (every request goes to headquarters)');

        $row = $this->onlyRow();
        self::assertSame('DEMO-A', $row['mission_assignment']);
        self::assertTrue($row['routed_to_headquarters']);
        self::assertSame('enquiry', $row['request_type']);
        self::assertNull($row['location_details']);
        self::assertContains('assistance.routed_mission', $this->db->fetchFirstColumn('SELECT action FROM audit_event WHERE target = ?', [$row['reference']]));
    }

    public function testUnapprovedRuleLeavesTheRequestWithHeadquarters(): void
    {
        $this->addSyntheticMission('KE', approved: false);
        $this->submit($this->validData());
        $this->client->followRedirect();
        self::assertSelectorTextContains('.routing', 'no single approved mission serves this country');
        self::assertNull($this->onlyRow()['mission_assignment']);
    }

    public function testMissingFieldsAreReportedAndLinked(): void
    {
        $this->submit(['assistance[requestType]' => null, 'assistance[message]' => '', 'assistance[country]' => '', 'assistance[syntheticConfirmation]' => '', 'assistance[notEmergencyAcknowledged]' => ''] + $this->validData());
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('title', 'Error:');
        $summary = $this->client->getCrawler()->filter('.error-summary');
        self::assertStringContainsString('Select whether you need help or have an enquiry.', $summary->text());
        self::assertStringContainsString('Describe your situation or enquiry.', $summary->text());
        self::assertStringContainsString('Select the country you are in now.', $summary->text());
        self::assertStringContainsString('not an emergency service', $summary->text());
        self::assertCount(1, $summary->filter('a[href="#assistance_requestType_0"]'));
        self::assertCount(1, $this->client->getCrawler()->filter('#assistance_requestType_0'));
        self::assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM assistance_request'));
    }

    public function testRealLookingEmailIsRefused(): void
    {
        $this->submit($this->validData(['assistance[email]' => 'someone@gmail.com']));
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.error-summary', 'only made-up addresses');
    }

    public function testOverlongDescriptionIsRefused(): void
    {
        $this->submit($this->validData(['assistance[message]' => str_repeat('a', 4001)]));
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.error-summary', '4,000 characters or fewer');
    }

    public function testHoneypotSubmissionIsRejected(): void
    {
        $this->submit($this->validData(['assistance[website]' => 'http://spam.example']));
        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM assistance_request'));
    }

    public function testDatabaseRefusesUnencryptedOrNonSyntheticRows(): void
    {
        $insert = "INSERT INTO assistance_request (status, routed_to_headquarters, data_class, created_at, reference, request_type, full_name, email, phone, country, city, message) VALUES ('submitted', %s, '%s', NOW(), 'SSD-TEST', 'distress', 'A', 'a@example.org', '+15550100123', 'KE', 'C', '%s')";
        foreach ([['true', 'real', 'v1:x'], ['true', 'synthetic', 'plain text'], ['false', 'synthetic', 'v1:x']] as [$hq, $class, $message]) {
            try {
                $this->db->executeStatement(sprintf($insert, $hq, $class, $message));
                self::fail(sprintf('Row accepted: hq=%s class=%s message=%s', $hq, $class, $message));
            } catch (\Doctrine\DBAL\Exception) {
                // Expected.
            }
        }
        self::assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM assistance_request'));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Registration\ReferenceGenerator;
use App\Security\FieldCipher;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RegistrationFormTest extends WebTestCase
{
    private KernelBrowser $client;
    private Connection $db;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->db = static::getContainer()->get(Connection::class);
        $this->db->executeStatement('DELETE FROM registration');
    }

    private function validData(array $overrides = []): array
    {
        return $overrides + [
            'registration[fullName]' => 'Amani Example',
            'registration[email]' => 'amani@example.org',
            'registration[phone]' => '+15550100123',
            'registration[nationality]' => 'SS',
            'registration[countryOfResidence]' => 'KE',
            'registration[cityOfResidence]' => 'Sample City',
            'registration[dateOfBirth]' => '1990-05-17',
            'registration[passportNumber]' => 'SYN123456',
            'registration[passportIssuingCountry]' => 'SS',
            'registration[passportExpiry]' => '2031-02-01',
            'registration[syntheticConfirmation]' => '1',
            'registration[privacyAcknowledged]' => '1',
        ];
    }

    private function submit(array $data): void
    {
        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->selectButton('Submit registration')->form();
        foreach (['registration[syntheticConfirmation]', 'registration[privacyAcknowledged]'] as $box) {
            if (empty($data[$box])) {
                $form[$box]->untick();
                unset($data[$box]);
            }
        }
        $this->client->submit($form, $data);
    }

    public function testFormPageShowsDemonstrationWarning(): void
    {
        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.demo-banner', 'Do not enter real personal or passport information');
        self::assertResponseHeaderSame('X-Robots-Tag', 'noindex, nofollow');
        self::assertStringContainsString('no-store', (string) $this->client->getResponse()->headers->get('Cache-Control'));
        self::assertStringContainsString("frame-ancestors 'none'", (string) $this->client->getResponse()->headers->get('Content-Security-Policy'));
    }

    public function testValidSubmissionStoresEncryptedRecordAndShowsReferenceOnce(): void
    {
        $this->submit($this->validData());
        self::assertResponseStatusCodeSame(303);
        self::assertResponseRedirects('/register/confirmation');
        self::assertStringNotContainsString('SSD-', (string) $this->client->getResponse()->headers->get('Location'));

        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        $reference = trim($this->client->getCrawler()->filter('.reference')->text());
        self::assertTrue(ReferenceGenerator::isValid($reference));

        $row = $this->db->fetchAssociative('SELECT r.*, s.passport_number AS pn, s.date_of_birth AS dob, s.passport_index FROM registration r JOIN registration_sensitive s ON s.registration_id = r.id WHERE r.reference = ?', [$reference]);
        self::assertSame('synthetic', $row['data_class']);
        self::assertSame('hq_queue', $row['assignment']);
        self::assertFalse((bool) $row['possible_duplicate']);
        self::assertStringNotContainsString('SYN123456', $row['pn']);
        self::assertStringNotContainsString('1990-05-17', $row['dob']);

        $cipher = static::getContainer()->get(FieldCipher::class);
        self::assertSame('SYN123456', $cipher->decrypt($row['pn'], 'passport_number'));
        self::assertSame('1990-05-17', $cipher->decrypt($row['dob'], 'date_of_birth'));

        $audit = $this->db->fetchAssociative('SELECT * FROM audit_event WHERE target = ? ORDER BY id DESC', [$reference]);
        self::assertSame('registration.created', $audit['action']);

        // Reloading the confirmation page does not show the reference again.
        $this->client->request('GET', '/register/confirmation');
        self::assertResponseRedirects('/register');
    }

    public function testRealLookingPassportAndEmailAreRefused(): void
    {
        $this->submit($this->validData([
            'registration[passportNumber]' => 'R00123456',
            'registration[email]' => 'someone@gmail.com',
        ]));
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.error-summary', 'only made-up passport numbers');
        self::assertSelectorTextContains('.error-summary', 'only made-up addresses');
        self::assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM registration'));
    }

    public function testMissingFieldsAreReportedAndLinked(): void
    {
        $this->submit(['registration[fullName]' => '', 'registration[syntheticConfirmation]' => '', 'registration[privacyAcknowledged]' => '']);
        self::assertResponseStatusCodeSame(422);
        self::assertPageTitleContains('Error:');
        self::assertSelectorExists('.error-summary a[href="#registration_fullName"]');
        self::assertSelectorExists('#registration_fullName[aria-invalid="true"][aria-describedby~="registration_fullName_error1"]');
        self::assertSelectorTextContains('.error-summary', 'Confirm that every detail you entered is made up.');
    }

    public function testHoneypotSubmissionIsRejected(): void
    {
        $this->submit($this->validData(['registration[website]' => 'http://spam.example']));
        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM registration'));
    }

    public function testDuplicatePassportIsFlaggedNotRejected(): void
    {
        $this->submit($this->validData());
        $this->submit($this->validData(['registration[fullName]' => 'Deng Sample']));
        self::assertResponseRedirects('/register/confirmation');
        $flags = $this->db->fetchFirstColumn('SELECT possible_duplicate FROM registration ORDER BY id');
        self::assertSame([false, true], array_map('boolval', $flags));
    }

    public function testDatabaseRefusesPlaintextAndNonSyntheticRows(): void
    {
        $this->submit($this->validData());
        $this->expectException(\Doctrine\DBAL\Exception::class);
        $this->db->executeStatement("UPDATE registration SET data_class = 'real'");
    }

    public function testDatabaseRefusesUnencryptedPassportNumbers(): void
    {
        $this->submit($this->validData());
        $this->expectException(\Doctrine\DBAL\Exception::class);
        $this->db->executeStatement("UPDATE registration_sensitive SET passport_number = 'SYN123456'");
    }

    public function testAuditLogIsAppendOnly(): void
    {
        $this->submit($this->validData());
        $this->expectException(\Doctrine\DBAL\Exception::class);
        $this->db->executeStatement('DELETE FROM audit_event');
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\JurisdictionRule;
use App\Entity\Mission;
use App\Entity\StaffUser;
use App\Registration\Registrar;
use App\Registration\RegistrationRequest;
use App\Security\FieldCipher;
use App\Security\Totp;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class StaffConsoleTest extends WebTestCase
{
    private const PASSWORD = 'correct horse battery staple';

    private KernelBrowser $client;
    private Connection $db;
    private EntityManagerInterface $em;
    /** @var array<string, string> */
    private array $secrets = [];
    /** @var array<string, string> reference by country */
    private array $refs = [];

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->db = $container->get(Connection::class);
        $this->em = $container->get(EntityManagerInterface::class);
        foreach (['registration', 'staff_user', 'jurisdiction_rule', 'mission'] as $table) {
            $this->db->executeStatement("DELETE FROM $table");
        }

        $a = new Mission('DEMO-A', 'Demonstration Mission A (synthetic)', Mission::STATUS_APPROVED);
        $b = new Mission('DEMO-B', 'Demonstration Mission B (synthetic)', Mission::STATUS_APPROVED);
        $c = new Mission('DEMO-C', 'Demonstration Mission C (synthetic, draft)');
        $rules = [
            new JurisdictionRule('KE', $a), new JurisdictionRule('ET', $a, JurisdictionRule::TYPE_NON_RESIDENT),
            new JurisdictionRule('UG', $b), new JurisdictionRule('EG', $c), new JurisdictionRule('US', $b),
            new JurisdictionRule('GB', $a, JurisdictionRule::TYPE_NON_RESIDENT), new JurisdictionRule('GB', $b, JurisdictionRule::TYPE_NON_RESIDENT),
        ];
        foreach ([$a, $b, $c] as $m) {
            $this->em->persist($m);
        }
        foreach ($rules as $rule) {
            if ($rule->getCountryCode() !== 'US') {
                $rule->approve('test');
            }
            $this->em->persist($rule);
        }
        $this->addStaff('mission.a@example.org', StaffUser::ROLE_MISSION_STAFF, $a);
        $this->addStaff('assign@example.org', StaffUser::ROLE_HQ_ASSIGNMENT);
        $this->addStaff('oversight@example.org', StaffUser::ROLE_HQ_OVERSIGHT);
        $this->addStaff('accounts@example.org', StaffUser::ROLE_ACCOUNT_ADMIN);
        $this->em->flush();

        $registrar = $container->get(Registrar::class);
        foreach (['KE', 'UG', 'ET', 'GB', 'EG', 'US'] as $i => $country) {
            $r = new RegistrationRequest();
            $r->fullName = 'Person '.$country;
            $r->email = strtolower($country).'@example.org';
            $r->phone = '+1555010012'.$i;
            $r->nationality = 'SS';
            $r->countryOfResidence = $country;
            $r->cityOfResidence = 'Sample City';
            $r->dateOfBirth = new \DateTimeImmutable('1990-01-0'.($i + 1));
            $r->passportNumber = 'SYN00000'.$i;
            $r->passportIssuingCountry = 'SS';
            $r->passportExpiry = new \DateTimeImmutable('2031-01-01');
            $r->nationalIdNumber = 'SYNID00000'.$i;
            $this->refs[$country] = $registrar->register($r, 'test');
        }
    }

    private function addStaff(string $email, string $role, ?Mission $mission = null): void
    {
        $secret = Totp::generateSecret();
        $this->secrets[$email] = $secret;
        $user = new StaffUser($email, ucfirst(strtok($email, '@')), $role, static::getContainer()->get(FieldCipher::class)->encrypt($secret, 'totp_secret'), $mission);
        $user->setPasswordHash(static::getContainer()->get('security.user_password_hasher')->hashPassword($user, self::PASSWORD));
        $this->em->persist($user);
    }

    private function login(string $email, ?string $code = null, string $password = self::PASSWORD): void
    {
        $crawler = $this->client->request('GET', '/staff/login');
        $form = $crawler->selectButton('Sign in')->form([
            'email' => $email,
            'password' => $password,
            'code' => $code ?? Totp::code($this->secrets[$email], Totp::currentStep()),
        ]);
        $this->client->submit($form);
    }

    private function assignment(string $country): string
    {
        return (string) $this->db->fetchOne('SELECT assignment FROM registration WHERE reference = ?', [$this->refs[$country]]);
    }

    public function testRoutingFollowsApprovedRulesOnly(): void
    {
        self::assertSame('DEMO-A', $this->assignment('KE'), 'single resident mission');
        self::assertSame('DEMO-B', $this->assignment('UG'));
        self::assertSame('DEMO-A', $this->assignment('ET'), 'single non-resident mission');
        self::assertSame('hq_queue', $this->assignment('GB'), 'overlapping missions');
        self::assertSame('hq_queue', $this->assignment('EG'), 'draft mission');
        self::assertSame('hq_queue', $this->assignment('US'), 'unapproved rule');
    }

    public function testStaffConsoleRequiresSignIn(): void
    {
        $this->client->request('GET', '/staff/records');
        self::assertResponseRedirects('/staff/login');
    }

    public function testWrongCodeOrPasswordGivesOneGenericMessage(): void
    {
        $this->login('oversight@example.org', '000000');
        $this->client->followRedirect();
        self::assertSelectorTextContains('[role="alert"]', 'email address, password or authenticator code is not correct');
        $this->login('oversight@example.org', null, 'wrong password');
        $this->client->followRedirect();
        self::assertSelectorTextContains('[role="alert"]', 'email address, password or authenticator code is not correct');
        $this->login('nobody@example.org', '123456');
        $this->client->followRedirect();
        self::assertSelectorTextContains('[role="alert"]', 'email address, password or authenticator code is not correct');
        self::assertGreaterThanOrEqual(3, (int) $this->db->fetchOne("SELECT COUNT(*) FROM audit_event WHERE action = 'staff.login' AND outcome = 'failure' AND occurred_at > now() - interval '1 minute'"));
    }

    public function testAuthenticatorCodeCannotBeReused(): void
    {
        $code = Totp::code($this->secrets['oversight@example.org'], Totp::currentStep());
        $this->login('oversight@example.org', $code);
        self::assertResponseRedirects('/staff');
        $this->client->request('GET', '/staff/logout');
        $this->client->getCookieJar()->clear();
        $this->login('oversight@example.org', $code);
        $this->client->followRedirect();
        self::assertSelectorExists('[role="alert"]');
    }

    public function testMissionStaffSeeOnlyTheirMission(): void
    {
        $this->login('mission.a@example.org');
        $crawler = $this->client->request('GET', '/staff/records');
        self::assertResponseIsSuccessful();
        $refs = $crawler->filter('tbody a')->each(fn ($a) => $a->text());
        sort($refs);
        $expected = [$this->refs['KE'], $this->refs['ET']];
        sort($expected);
        self::assertSame($expected, $refs);

        // Another mission's record and the HQ queue are invisible.
        $this->client->request('GET', '/staff/records/'.$this->refs['UG']);
        self::assertResponseStatusCodeSame(404);
        $this->client->request('GET', '/staff/records/'.$this->refs['GB']);
        self::assertResponseStatusCodeSame(404);
        $this->client->request('GET', '/staff/records?assignment=DEMO-B');
        self::assertSelectorNotExists('tbody a[href$="'.$this->refs['UG'].'"]');
        $this->client->request('GET', '/staff/audit');
        self::assertResponseStatusCodeSame(403);
    }

    public function testRowLevelSecurityFiltersEvenUnfilteredQueries(): void
    {
        $this->db->executeQuery("SELECT set_config('app.scope', 'mission', false), set_config('app.mission', 'DEMO-B', false)");
        self::assertSame([$this->refs['UG']], $this->db->fetchFirstColumn('SELECT reference FROM registration'));
        self::assertSame(1, (int) $this->db->fetchOne('SELECT COUNT(*) FROM registration_sensitive'));

        $this->db->executeQuery("SELECT set_config('app.scope', 'accounts', false)");
        self::assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM registration'));
        self::assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM registration_sensitive'));

        // A mission session cannot move a record out of or into its scope.
        $this->db->executeQuery("SELECT set_config('app.scope', 'mission', false), set_config('app.mission', 'DEMO-B', false)");
        self::assertSame(0, $this->db->executeStatement("UPDATE registration SET assignment = 'DEMO-B' WHERE assignment = 'DEMO-A'"));

        $this->db->executeQuery("SELECT set_config('app.scope', 'hq', false)");
        self::assertSame(6, (int) $this->db->fetchOne('SELECT COUNT(*) FROM registration'));
        $this->db->executeQuery("SELECT set_config('app.scope', '', false), set_config('app.mission', '', false)");
    }

    public function testDocumentNumbersAreMaskedUntilARevealIsLogged(): void
    {
        $this->login('mission.a@example.org');
        $crawler = $this->client->request('GET', '/staff/records/'.$this->refs['KE']);
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('SYN000000', $crawler->filter('main')->text());
        self::assertStringContainsString('•••••••00', $crawler->filter('main')->text());
        self::assertSelectorTextContains('.watermark', 'mission.a@example.org');

        $crawler = $this->client->submit($crawler->selectButton('Reveal document numbers')->form());
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('SYN000000', $crawler->filter('main')->text());
        self::assertStringContainsString('SYNID000000', $crawler->filter('main')->text());
        $actions = $this->db->fetchFirstColumn('SELECT action FROM audit_event WHERE target = ? ORDER BY id', [$this->refs['KE']]);
        self::assertSame(['registration.created', 'record.viewed', 'record.revealed'], $actions);
    }

    public function testAssignmentOfficerReassignsButCannotReveal(): void
    {
        $this->login('assign@example.org');
        $crawler = $this->client->request('GET', '/staff/records/'.$this->refs['GB']);
        self::assertSelectorNotExists('input[value="Reveal document numbers"]');
        $this->client->request('POST', '/staff/records/'.$this->refs['GB'].'/reveal');
        self::assertResponseStatusCodeSame(403);

        $crawler = $this->client->request('GET', '/staff/records/'.$this->refs['GB']);
        $this->client->submit($crawler->selectButton('Reassign record')->form(['assignment' => 'DEMO-B', 'reason' => '']));
        self::assertResponseStatusCodeSame(422);
        self::assertSame('hq_queue', $this->assignment('GB'));

        $crawler = $this->client->request('GET', '/staff/records/'.$this->refs['GB']);
        $this->client->submit($crawler->selectButton('Reassign record')->form(['assignment' => 'DEMO-B', 'reason' => 'Registrant lives near Mission B (synthetic).']));
        self::assertResponseRedirects('/staff/records/'.$this->refs['GB']);
        self::assertSame('DEMO-B', $this->assignment('GB'));
        self::assertSame('hq_queue', $this->db->fetchOne('SELECT from_assignment FROM assignment_history'));
        self::assertSame(1, (int) $this->db->fetchOne("SELECT COUNT(*) FROM audit_event WHERE action = 'record.reassigned' AND target = ?", [$this->refs['GB']]));
    }

    public function testDraftMissionCannotBeChosen(): void
    {
        $this->login('assign@example.org');
        $crawler = $this->client->request('GET', '/staff/records/'.$this->refs['EG']);
        $token = $crawler->filter('input[name="_token"]')->last()->attr('value');
        $this->client->request('POST', '/staff/records/'.$this->refs['EG'].'/assign', ['_token' => $token, 'assignment' => 'DEMO-C', 'reason' => 'test']);
        self::assertResponseStatusCodeSame(422);
        self::assertSame('hq_queue', $this->assignment('EG'));
    }

    public function testMissionStaffCannotReassign(): void
    {
        $this->login('mission.a@example.org');
        $this->client->request('POST', '/staff/records/'.$this->refs['KE'].'/assign', ['assignment' => 'hq_queue', 'reason' => 'x']);
        self::assertResponseStatusCodeSame(403);
    }

    public function testAccountAdministratorManagesAccountsButSeesNoRecords(): void
    {
        $this->login('accounts@example.org');
        $this->client->request('GET', '/staff/records');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/staff/records/'.$this->refs['KE']);
        self::assertResponseStatusCodeSame(403);

        $crawler = $this->client->request('GET', '/staff/accounts');
        self::assertResponseIsSuccessful();
        $row = $crawler->filter('tr')->reduce(fn ($tr) => str_contains($tr->text(), 'mission.a@example.org'));
        $this->client->submit($row->selectButton('Deactivate mission.a@example.org')->form());
        self::assertResponseRedirects('/staff/accounts');

        $this->client->request('GET', '/staff/logout');
        $this->client->getCookieJar()->clear();
        $this->login('mission.a@example.org');
        $this->client->followRedirect();
        self::assertSelectorTextContains('[role="alert"]', 'not correct');
    }

    public function testOversightReadsTheAuditLog(): void
    {
        $this->login('oversight@example.org');
        $this->client->request('GET', '/staff/audit');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', 'staff.login');
    }
}

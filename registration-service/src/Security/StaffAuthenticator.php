<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\AuditEvent;
use App\Entity\StaffUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Staff sign-in: email, password and a code from an authenticator app,
 * checked together. Every failure gets the same message, so the form does
 * not reveal whether an account exists, is deactivated or which part was
 * wrong. Each attempt is written to the audit log.
 */
final class StaffAuthenticator extends AbstractLoginFormAuthenticator
{
    public const LOGIN_ROUTE = 'staff_login';
    private const FAILURE = 'The email address, password or authenticator code is not correct.';

    private ?int $acceptedStep = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly FieldCipher $cipher,
        private readonly UrlGeneratorInterface $urls,
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $email = strtolower(trim((string) $request->request->get('email', '')));
        $password = (string) $request->request->get('password', '');
        $code = preg_replace('/\s+/', '', (string) $request->request->get('code', ''));
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email, function (string $identifier): StaffUser {
                $user = $this->entityManager->getRepository(StaffUser::class)->findOneBy(['email' => $identifier]);
                if (!$user instanceof StaffUser || !$user->isActive()) {
                    throw new UserNotFoundException();
                }

                return $user;
            }),
            new CustomCredentials(function (string $code, StaffUser $user) use ($password): bool {
                if (!$this->hasher->isPasswordValid($user, $password)) {
                    return false;
                }
                $secret = $this->cipher->decrypt($user->getTotpSecret(), 'totp_secret');
                $this->acceptedStep = Totp::verify($secret, $code, $user->getLastTotpStep());

                return $this->acceptedStep !== null;
            }, $code),
            [new CsrfTokenBadge('staff_login', (string) $request->request->get('_csrf_token'))],
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user instanceof StaffUser && $this->acceptedStep !== null) {
            $user->recordLogin($this->acceptedStep);
            $this->entityManager->persist(new AuditEvent($user->getEmail(), 'staff.login', null));
            $this->entityManager->flush();
        }

        return new RedirectResponse($this->urls->generate('staff_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $email = (string) $request->getSession()->get(SecurityRequestAttributes::LAST_USERNAME, '');
        $this->entityManager->persist(new AuditEvent(substr($email ?: 'unknown', 0, 64), 'staff.login', null, 'failure'));
        $this->entityManager->flush();

        return parent::onAuthenticationFailure($request, new CustomUserMessageAuthenticationException(self::FAILURE));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urls->generate(self::LOGIN_ROUTE);
    }
}

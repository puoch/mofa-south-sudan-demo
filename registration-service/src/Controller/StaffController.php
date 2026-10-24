<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AssignmentHistory;
use App\Entity\AuditEvent;
use App\Entity\Mission;
use App\Entity\Registration;
use App\Entity\StaffUser;
use App\Registration\RecordPresenter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * The staff console. Access follows the role matrix in the architecture
 * review: mission staff see their own mission's records, HQ assignment
 * officers see and reassign every record, HQ oversight officers see every
 * record and the audit log, and account administrators see no records.
 *
 * Exports, printing and corrections are not built: their rules are pending.
 */
#[Route('/staff')]
final class StaffController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RecordPresenter $presenter,
    ) {
    }

    #[Route('/login', name: 'staff_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $auth): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('staff_home');
        }

        return $this->render('staff/login.html.twig', [
            'last_email' => $auth->getLastUsername(),
            'error' => $auth->getLastAuthenticationError(),
        ], new Response('', $auth->getLastAuthenticationError(false) ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/logout', name: 'staff_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException('Handled by the firewall.');
    }

    #[Route('', name: 'staff_home', methods: ['GET'])]
    public function home(#[CurrentUser] StaffUser $user): Response
    {
        return $this->redirectToRoute($user->getRole() === StaffUser::ROLE_ACCOUNT_ADMIN ? 'staff_accounts' : 'staff_records');
    }

    #[Route('/records', name: 'staff_records', methods: ['GET'])]
    public function records(Request $request, #[CurrentUser] StaffUser $user): Response
    {
        $this->denyUnlessRecordReader($user);
        $qb = $this->entityManager->getRepository(Registration::class)->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(100);
        $assignment = (string) $request->query->get('assignment', '');
        if ($user->getRole() === StaffUser::ROLE_MISSION_STAFF) {
            // Database row-level security applies the same rule underneath.
            $assignment = $user->getMission()->getCode();
        }
        if ($assignment !== '') {
            $qb->andWhere('r.assignment = :assignment')->setParameter('assignment', $assignment);
        }

        return $this->render('staff/records.html.twig', [
            'records' => $qb->getQuery()->getResult(),
            'assignment' => $assignment,
            'missions' => $this->missions(),
        ]);
    }

    #[Route('/records/{reference}', name: 'staff_record', methods: ['GET'])]
    public function record(string $reference, #[CurrentUser] StaffUser $user): Response
    {
        $registration = $this->visibleRecord($reference, $user);
        $this->audit($user, 'record.viewed', $registration);

        return $this->renderRecord($registration, $user, false);
    }

    #[Route('/records/{reference}/reveal', name: 'staff_record_reveal', methods: ['POST'])]
    public function reveal(string $reference, Request $request, #[CurrentUser] StaffUser $user): Response
    {
        $registration = $this->visibleRecord($reference, $user);
        if (!$this->canReveal($user)) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('reveal-'.$reference, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid form token.');
        }
        $this->audit($user, 'record.revealed', $registration);

        return $this->renderRecord($registration, $user, true);
    }

    #[Route('/records/{reference}/assign', name: 'staff_record_assign', methods: ['POST'])]
    #[IsGranted(StaffUser::ROLE_HQ_ASSIGNMENT)]
    public function assign(string $reference, Request $request, #[CurrentUser] StaffUser $user): Response
    {
        $registration = $this->visibleRecord($reference, $user);
        if (!$this->isCsrfTokenValid('assign-'.$reference, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid form token.');
        }
        $to = (string) $request->request->get('assignment');
        $reason = trim((string) $request->request->get('reason'));
        $allowed = array_merge([Registration::ASSIGNMENT_HQ_QUEUE], array_map(fn (Mission $m) => $m->getCode(), $this->missions()));
        $errors = [];
        if (!in_array($to, $allowed, true)) {
            $errors[] = 'Select an approved mission or the headquarters queue.';
        } elseif ($to === $registration->getAssignment()) {
            $errors[] = 'The record is already assigned there.';
        }
        if ($reason === '' || mb_strlen($reason) > 300) {
            $errors[] = 'Give a reason for the change, in 300 characters or fewer.';
        }
        if ($errors) {
            return $this->renderRecord($registration, $user, false, $errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $from = $registration->getAssignment();
        $registration->assignTo($to);
        $this->entityManager->persist(new AssignmentHistory($registration, $from, $to, $user->getEmail(), $reason));
        $this->entityManager->persist(new AuditEvent($user->getEmail(), 'record.reassigned', $registration->getReference()));
        $this->entityManager->flush();
        $this->addFlash('success', sprintf('Record reassigned to %s.', $to === Registration::ASSIGNMENT_HQ_QUEUE ? 'the headquarters queue' : $to));

        return $this->redirectToRoute('staff_record', ['reference' => $reference], Response::HTTP_SEE_OTHER);
    }

    #[Route('/audit', name: 'staff_audit', methods: ['GET'])]
    public function auditLog(): Response
    {
        $events = $this->entityManager->getRepository(AuditEvent::class)->findBy([], ['id' => 'DESC'], 200);

        return $this->render('staff/audit.html.twig', ['events' => $events]);
    }

    #[Route('/accounts', name: 'staff_accounts', methods: ['GET'])]
    public function accounts(): Response
    {
        return $this->render('staff/accounts.html.twig', [
            'accounts' => $this->entityManager->getRepository(StaffUser::class)->findBy([], ['email' => 'ASC']),
        ]);
    }

    #[Route('/accounts/{id}/active', name: 'staff_account_active', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function setActive(int $id, Request $request, #[CurrentUser] StaffUser $user): Response
    {
        $account = $this->entityManager->find(StaffUser::class, $id) ?? throw new NotFoundHttpException();
        if (!$this->isCsrfTokenValid('account-'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid form token.');
        }
        if ($account === $user) {
            $this->addFlash('error', 'You cannot deactivate your own account.');

            return $this->redirectToRoute('staff_accounts', [], Response::HTTP_SEE_OTHER);
        }
        $active = $request->request->get('active') === '1';
        $account->setActive($active);
        $this->entityManager->persist(new AuditEvent($user->getEmail(), $active ? 'account.activated' : 'account.deactivated', 'staff:'.$account->getId()));
        $this->entityManager->flush();
        $this->addFlash('success', sprintf('%s is now %s.', $account->getEmail(), $active ? 'active' : 'deactivated'));

        return $this->redirectToRoute('staff_accounts', [], Response::HTTP_SEE_OTHER);
    }

    private function renderRecord(Registration $registration, StaffUser $user, bool $revealed, array $errors = [], int $status = Response::HTTP_OK): Response
    {
        return $this->render('staff/record.html.twig', [
            'record' => $registration,
            'sensitive' => $this->presenter->sensitive($registration, $revealed),
            'revealed' => $revealed,
            'can_reveal' => $this->canReveal($user) && $this->presenter->hasIdentityDocument($registration),
            'can_assign' => $this->isGranted(StaffUser::ROLE_HQ_ASSIGNMENT),
            'missions' => $this->missions(),
            'history' => $this->entityManager->getRepository(AssignmentHistory::class)->findBy(['registration' => $registration], ['id' => 'DESC']),
            'errors' => $errors,
            'viewed_at' => new \DateTimeImmutable(),
        ], new Response('', $status));
    }

    private function visibleRecord(string $reference, StaffUser $user): Registration
    {
        $this->denyUnlessRecordReader($user);
        $registration = $this->entityManager->getRepository(Registration::class)->findOneBy(['reference' => $reference]);
        // Row-level security already hides other missions' records; this
        // check makes the rule explicit in the application as well.
        if ($registration === null || ($user->getRole() === StaffUser::ROLE_MISSION_STAFF && $registration->getAssignment() !== $user->getMission()->getCode())) {
            throw new NotFoundHttpException();
        }

        return $registration;
    }

    private function denyUnlessRecordReader(StaffUser $user): void
    {
        if ($user->getRole() === StaffUser::ROLE_ACCOUNT_ADMIN) {
            throw $this->createAccessDeniedException();
        }
    }

    private function canReveal(StaffUser $user): bool
    {
        return in_array($user->getRole(), [StaffUser::ROLE_MISSION_STAFF, StaffUser::ROLE_HQ_OVERSIGHT], true);
    }

    private function audit(StaffUser $user, string $action, Registration $registration): void
    {
        $this->entityManager->persist(new AuditEvent($user->getEmail(), $action, $registration->getReference()));
        $this->entityManager->flush();
    }

    /**
     * @return Mission[]
     */
    private function missions(): array
    {
        return $this->entityManager->getRepository(Mission::class)->findBy(['status' => Mission::STATUS_APPROVED], ['code' => 'ASC']);
    }
}

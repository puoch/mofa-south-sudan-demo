<?php

declare(strict_types=1);

namespace App\Controller;

use App\Assistance\AssistanceDesk;
use App\Assistance\AssistanceSubmission;
use App\Form\AssistanceType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

final class AssistanceController extends AbstractController
{
    private const SESSION_RESULT = 'assistance_result';

    #[Route('/assistance', name: 'assistance', methods: ['GET', 'POST'])]
    public function request(Request $request, AssistanceDesk $desk, RateLimiterFactoryInterface $assistanceSubmitLimiter): Response
    {
        $form = $this->createForm(AssistanceType::class, new AssistanceSubmission());
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $limit = $assistanceSubmitLimiter->create($request->getClientIp() ?? 'unknown')->consume();
            if (!$limit->isAccepted()) {
                return $this->render('registration/too_many.html.twig', [], new Response('', Response::HTTP_TOO_MANY_REQUESTS));
            }
            if ($form->isValid()) {
                $result = $desk->submit($form->getData());
                // As for registrations, the reference stays out of the URL.
                $request->getSession()->set(self::SESSION_RESULT, [
                    'reference' => $result['reference'],
                    'type' => $form->getData()->requestType,
                    'mission_name' => $result['mission'],
                ]);

                return $this->redirectToRoute('assistance_confirmation', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('assistance/form.html.twig', ['form' => $form], new Response('', $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/assistance/confirmation', name: 'assistance_confirmation', methods: ['GET'])]
    public function confirmation(Request $request): Response
    {
        $result = $request->getSession()->remove(self::SESSION_RESULT);
        if ($result === null) {
            return $this->redirectToRoute('assistance');
        }

        return $this->render('assistance/confirmation.html.twig', $result);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\RegistrationType;
use App\Registration\Registrar;
use App\Registration\RegistrationRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    private const SESSION_REFERENCE = 'registration_reference';

    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->redirectToRoute('register');
    }

    #[Route('/register', name: 'register', methods: ['GET', 'POST'])]
    public function register(Request $request, Registrar $registrar, RateLimiterFactoryInterface $registrationSubmitLimiter): Response
    {
        $form = $this->createForm(RegistrationType::class, new RegistrationRequest());
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $limit = $registrationSubmitLimiter->create($request->getClientIp() ?? 'unknown')->consume();
            if (!$limit->isAccepted()) {
                return $this->render('registration/too_many.html.twig', [], new Response('', Response::HTTP_TOO_MANY_REQUESTS));
            }
            if ($form->isValid()) {
                $reference = $registrar->register($form->getData());
                // The reference goes in the session, not the URL, so it never
                // appears in server logs or browser history.
                $request->getSession()->set(self::SESSION_REFERENCE, $reference);

                return $this->redirectToRoute('register_confirmation', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('registration/form.html.twig', ['form' => $form], new Response('', $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/register/confirmation', name: 'register_confirmation', methods: ['GET'])]
    public function confirmation(Request $request): Response
    {
        // Shown once: reloading or sharing the page does not reveal it again.
        $reference = $request->getSession()->remove(self::SESSION_REFERENCE);
        if ($reference === null) {
            return $this->redirectToRoute('register');
        }

        return $this->render('registration/confirmation.html.twig', ['reference' => $reference]);
    }

    #[Route('/privacy', name: 'privacy', methods: ['GET'])]
    public function privacy(): Response
    {
        return $this->render('registration/privacy.html.twig');
    }
}

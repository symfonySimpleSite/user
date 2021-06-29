<?php

namespace SymfonySimpleSite\User\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use SymfonySimpleSite\Common\Interfaces\StatusInterface;
use SymfonySimpleSite\Page\Service\GetTemplateService;
use SymfonySimpleSite\User\Entity\User;
use SymfonySimpleSite\User\Form\RegistrationFormType;
use SymfonySimpleSite\User\Security\EmailVerifier;
use SymfonySimpleSite\User\Security\SymfonySimpleSieAuthenticator;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends UserAbstractController
{
    public function register(
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder,
        GuardAuthenticatorHandler $guardHandler,
        SymfonySimpleSieAuthenticator $authenticator,
        EmailVerifier $emailVerifier
    ): Response
    {
        $user = new User();
        $user->setCreatedAt(new \DateTime('now'));
        $user->setStatus(StatusInterface::STATUS_ACTIVE);
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $emailVerifier->sendEmailConfirmation('user_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('test@user.com', 'Mail Bot'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('@User/registration/confirmation_email.html.twig')
            );

            return $guardHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $authenticator,
                'main' // firewall name in security.yaml
            );
        }

        return $this->render('@User/registration/register.html.twig', [
            'template' => $this->getTemplate(),
            'registrationForm' => $form->createView(),
        ]);
    }

    public function verifyUserEmail(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $exception->getReason());

            return $this->redirectToRoute('user_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('user_register');
    }
}


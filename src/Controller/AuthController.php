<?php

namespace App\Controller;

use App\Form\LoginType;
use App\Form\ForgotPasswordType;
use App\Form\ResetPasswordType;
use App\Form\ChangePasswordType;
use App\Service\AuthChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use ReCaptcha\ReCaptcha;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AuthController extends AbstractController
{
    private $requestStack;
    private $params;

    public function __construct(RequestStack $requestStack, ParameterBagInterface $params)
    {
        $this->requestStack = $requestStack;
        $this->params = $params;
    }

    #[Route('/login', name: 'app_login')]
    public function login(Request $request, AuthChecker $authChecker): Response
    {
        // If already logged in, go to home
        if ($authChecker->isLoggedIn()) {
            $this->addFlash('info', 'Vous êtes déjà connecté.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);

        // Get any authentication errors from the session
        $error = null;
        $session = $this->requestStack->getSession();
        $lastError = $session->get('_security.last_error');
        if ($lastError) {
            $error = $lastError;
            $session->remove('_security.last_error');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $recaptchaSecret = (string) $this->params->get('google_recaptcha_secret');
            $reCaptcha = new ReCaptcha($recaptchaSecret);

            // Verify reCAPTCHA v2
            $recaptchaResponse = $request->request->get('g-recaptcha-response');

            if (empty($recaptchaResponse)) {
                $error = 'Veuillez confirmer que vous n\'êtes pas un robot.';

                return $this->render('auth/login.html.twig', [
                    'form' => $form->createView(),
                    'error' => $error,
                    'google_recaptcha_site_key' => $this->params->get('google_recaptcha_site_key')
                ]);
            }

            $resp = $reCaptcha->verify($recaptchaResponse, $request->getClientIp());

            if (!$resp->isSuccess()) {
                $error = 'La validation reCAPTCHA a échoué. Veuillez réessayer.';
                $errorCodes = $resp->getErrorCodes();
                if (!empty($errorCodes)) {
                    // Log the error codes for debugging
                    error_log('reCAPTCHA error: ' . implode(', ', $errorCodes));
                }

                return $this->render('auth/login.html.twig', [
                    'form' => $form->createView(),
                    'error' => $error,
                    'google_recaptcha_site_key' => $this->params->get('google_recaptcha_site_key')
                ]);
            }

            // Continue with login
            $data = $form->getData();
            $email = $data['email'];
            $password = $data['password'];

            $user = $authChecker->login($email, $password);

            if ($user) {
                $this->addFlash('success', 'Connexion réussie ! Bienvenue ' . $user->getPrenom() . '!');
                return $this->redirectToRoute('app_home');
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        } elseif ($form->isSubmitted()) {
            $error = 'Veuillez vérifier les informations saisies.';
        }

        return $this->render('auth/login.html.twig', [
            'form' => $form->createView(),
            'error' => $error,
            'google_recaptcha_site_key' => $this->params->get('google_recaptcha_site_key')
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(AuthChecker $authChecker): Response
    {
        $authChecker->logout();
        $this->addFlash('success', 'Vous avez été déconnecté.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request, AuthChecker $authChecker): Response
    {
        // If already logged in, no need for password reset
        if ($authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        $message = null;
        $error = null;

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();
                $email = $data['email'];

                $result = $authChecker->createPasswordReset($email);

                if ($result) {
                    $message = [
                        'type' => 'success',
                        'text' => 'Un email avec les instructions de réinitialisation a été envoyé à votre adresse.',
                        'note' => 'Vérifiez votre boîte de réception (et vos spams).'
                    ];
                } else {
                    $message = [
                        'type' => 'info',
                        'text' => 'Si votre email est enregistré, vous recevrez un lien de réinitialisation.'
                    ];
                }
            } else {
                $error = 'Veuillez vérifier les informations saisies.';
            }
        }

        return $this->render('auth/forgot_password.html.twig', [
            'form' => $form->createView(),
            'message' => $message,
            'error' => $error
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(Request $request, AuthChecker $authChecker, string $token): Response
    {
        // If already logged in, redirect to change password page
        if ($authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_change_password');
        }

        $user = $authChecker->isValidResetToken($token);

        if (!$user) {
            $this->addFlash('error', 'Lien invalide ou expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        $error = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $password = $data['password'];

            $success = $authChecker->resetPasswordWithToken($token, $password);

            if ($success) {
                $this->addFlash('success', 'Mot de passe changé avec succès. Connectez-vous.');
                return $this->redirectToRoute('app_login');
            } else {
                $error = 'Erreur lors de la réinitialisation. Réessayez.';
            }
        }

        return $this->render('auth/reset_password.html.twig', [
            'form' => $form->createView(),
            'token' => $token,
            'user' => $user,
            'error' => $error
        ]);
    }

    #[Route('/change-password', name: 'app_change_password')]
    public function changePassword(Request $request, AuthChecker $authChecker): Response
    {
        // Must be logged in
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Connectez-vous d\'abord.');
            return $this->redirectToRoute('app_login');
        }

        $user = $authChecker->getCurrentUser();

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        $error = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $currentPassword = $data['currentPassword'];
            $newPassword = $data['newPassword'];

            $success = $authChecker->changePassword($user, $currentPassword, $newPassword);

            if ($success) {
                $this->addFlash('success', 'Mot de passe changé avec succès.');
                return $this->redirectToRoute('app_home');
            } else {
                $error = 'Mot de passe actuel incorrect.';
            }
        }

        return $this->render('auth/change_password.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'error' => $error
        ]);
    }

    // Route for signup - redirects to login with message
    #[Route('/signup', name: 'app_signup')]
    public function signup(): Response
    {
        $this->addFlash('info', 'Pour créer un compte, contactez l\'administration.');
        return $this->redirectToRoute('app_login');
    }
}

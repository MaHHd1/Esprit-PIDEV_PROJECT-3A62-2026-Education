<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use App\Form\ForgotPasswordType;
use App\Form\LoginType;
use App\Form\ResetPasswordType;
use App\Service\AuthChecker;
use ReCaptcha\ReCaptcha;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    private RequestStack $requestStack;
    private ParameterBagInterface $params;

    public function __construct(RequestStack $requestStack, ParameterBagInterface $params)
    {
        $this->requestStack = $requestStack;
        $this->params = $params;
    }

    #[Route('/login', name: 'app_login')]
    public function login(Request $request, AuthChecker $authChecker): Response
    {
        $recaptchaSiteKey = trim((string) $this->params->get('google_recaptcha_site_key'));
        $recaptchaSecret = trim((string) $this->params->get('google_recaptcha_secret'));
        $recaptchaEnabled = $recaptchaSiteKey !== '' && $recaptchaSecret !== '';

        if ($authChecker->isLoggedIn()) {
            $this->addFlash('info', 'Vous etes deja connecte.');

            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);

        $error = null;
        $session = $this->requestStack->getSession();
        $lastError = $session->get('_security.last_error');
        if ($lastError) {
            $error = $lastError;
            $session->remove('_security.last_error');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if ($recaptchaEnabled) {
                $reCaptcha = new ReCaptcha($recaptchaSecret);
                $recaptchaResponse = $request->request->get('g-recaptcha-response');

                if (empty($recaptchaResponse)) {
                    $error = 'Veuillez confirmer que vous n\'etes pas un robot.';

                    return $this->render('auth/login.html.twig', [
                        'form' => $form->createView(),
                        'error' => $error,
                        'google_recaptcha_site_key' => $recaptchaSiteKey,
                        'recaptcha_enabled' => $recaptchaEnabled,
                    ]);
                }

                $resp = $reCaptcha->verify($recaptchaResponse, $request->getClientIp());

                if (!$resp->isSuccess()) {
                    $error = 'La validation reCAPTCHA a echoue. Veuillez reessayer.';
                    $errorCodes = $resp->getErrorCodes();
                    if (!empty($errorCodes)) {
                        error_log('reCAPTCHA error: ' . implode(', ', $errorCodes));
                    }

                    return $this->render('auth/login.html.twig', [
                        'form' => $form->createView(),
                        'error' => $error,
                        'google_recaptcha_site_key' => $recaptchaSiteKey,
                        'recaptcha_enabled' => $recaptchaEnabled,
                    ]);
                }
            }

            $data = $form->getData();
            $email = $data['email'];
            $password = $data['password'];

            $user = $authChecker->login($email, $password);

            if ($user) {
                $this->addFlash('success', 'Connexion reussie ! Bienvenue ' . $user->getPrenom() . '!');

                return $this->redirectToRoute('app_home');
            }

            $error = 'Email ou mot de passe incorrect.';
        } elseif ($form->isSubmitted()) {
            $error = 'Veuillez verifier les informations saisies.';
        }

        return $this->render('auth/login.html.twig', [
            'form' => $form->createView(),
            'error' => $error,
            'google_recaptcha_site_key' => $recaptchaSiteKey,
            'recaptcha_enabled' => $recaptchaEnabled,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(AuthChecker $authChecker): Response
    {
        $authChecker->logout();
        $this->addFlash('success', 'Vous avez ete deconnecte.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request, AuthChecker $authChecker): Response
    {
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
                        'text' => 'Un email avec les instructions de reinitialisation a ete envoye a votre adresse.',
                        'note' => 'Verifiez votre boite de reception (et vos spams).',
                    ];
                } else {
                    $message = [
                        'type' => 'info',
                        'text' => 'Si votre email est enregistre, vous recevrez un lien de reinitialisation.',
                    ];
                }
            } else {
                $error = 'Veuillez verifier les informations saisies.';
            }
        }

        return $this->render('auth/forgot_password.html.twig', [
            'form' => $form->createView(),
            'message' => $message,
            'error' => $error,
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(Request $request, AuthChecker $authChecker, string $token): Response
    {
        if ($authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_change_password');
        }

        $user = $authChecker->isValidResetToken($token);

        if (!$user) {
            $this->addFlash('error', 'Lien invalide ou expire.');

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
                $this->addFlash('success', 'Mot de passe change avec succes. Connectez-vous.');

                return $this->redirectToRoute('app_login');
            }

            $error = 'Erreur lors de la reinitialisation. Reessayez.';
        }

        return $this->render('auth/reset_password.html.twig', [
            'form' => $form->createView(),
            'token' => $token,
            'user' => $user,
            'error' => $error,
        ]);
    }

    #[Route('/change-password', name: 'app_change_password')]
    public function changePassword(Request $request, AuthChecker $authChecker): Response
    {
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
                $this->addFlash('success', 'Mot de passe change avec succes.');

                return $this->redirectToRoute('app_home');
            }

            $error = 'Mot de passe actuel incorrect.';
        }

        return $this->render('auth/change_password.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'error' => $error,
        ]);
    }

    #[Route('/signup', name: 'app_signup')]
    public function signup(): Response
    {
        $this->addFlash('info', 'Pour creer un compte, contactez l\'administration.');

        return $this->redirectToRoute('app_login');
    }
}

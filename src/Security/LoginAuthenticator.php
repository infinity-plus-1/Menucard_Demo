<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * @see https://symfony.com/doc/current/security/custom_authenticator.html
 */
class LoginAuthenticator extends AbstractAuthenticator
{
    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     */

     private $csrfTokenManager;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    
    public function supports(Request $request): ?bool
    {
        return $request->isMethod('POST') && $request->getPathInfo() === '/ajax-login';
    }

    public function authenticate(Request $request): Passport
    {

        $parameters = $request->request->all();

        $csrfToken = $parameters['_csrf_token'];

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('authenticate', $csrfToken))) {
            throw new CustomUserMessageAuthenticationException('Invalid CSRF token.');
        }


        $abort = false;

        if (!isset($parameters['email']) || !isset($parameters['password'])) {
            $abort = true;
        }

        if ($parameters['email'] === '' || $parameters['password'] === '') {
            $abort = true;
        }

        if ($abort) {
            throw new CustomUserMessageAuthenticationException('Invalid data');
        }

        $email = $parameters['email'];
        $password = $parameters['password'];

        // implement your own logic to get the user identifier from `$apiToken`
        // e.g. by looking up a user in the database using its API key
        // $userIdentifier = /** ... */;

        //return new SelfValidatingPassport(new UserBadge($email));
        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $data = [
            'message' => 'Login successful',
            'user' => $token->getUser()->getUserIdentifier(),
            'status' => 1
            // Optionally include roles or other data:
            // 'roles' => $token->getRoleNames(),
        ];
    
        return new JsonResponse($data, Response::HTTP_OK);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Access the session directly
        $session = $request->getSession();
        
        // Remove the last error from the session (if it exists)
        if ($session) {
            $session->remove('_security.last_error');
        }
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];
        

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    // public function start(Request $request, AuthenticationException $authException = null): Response
    // {
    //     /*
    //      * If you would like this class to control what happens when an anonymous user accesses a
    //      * protected page (e.g. redirect to /login), uncomment this method and make this class
    //      * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
    //      *
    //      * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
    //      */
    // }
}

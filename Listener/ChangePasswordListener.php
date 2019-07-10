<?php

namespace ACSEO\ChangePasswordBundle\Listener;

use ACSEO\ChangePasswordBundle\Entity\PasswordHistory;
use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ChangePasswordListener implements EventSubscriberInterface
{
    public function __construct(EntityManager $em, TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker, Router $router, $passwordExpireAfter, $changePasswordRoute, $enableFlashbagMessage, $avoidRole)
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->router = $router;
        $this->passwordExpireAfter = $passwordExpireAfter;
        $this->changePasswordRoute = $changePasswordRoute;
        $this->enableFlashbagMessage = $enableFlashbagMessage;
        $this->avoidRole = $avoidRole;
    }

    public static function getSubscribedEvents()
    {
        return array(FOSUserEvents::CHANGE_PASSWORD_COMPLETED => 'onChangePasswordCompleted',
                      FOSUserEvents::REGISTRATION_COMPLETED => 'onChangePasswordCompleted',
                      FOSUserEvents::RESETTING_RESET_COMPLETED => 'onChangePasswordCompleted',
                      KernelEvents::REQUEST => 'onKernelRequest',
        );
    }

    public function onChangePasswordCompleted(FilterUserResponseEvent $event)
    {
        $user = $event->getUser();

        $passwordHistory = new PasswordHistory();
        $passwordHistory->setUser($user);
        $passwordHistory->setPassword($user->getPassword());
        $passwordHistory->setSalt($user->getSalt());

        $this->em->persist($passwordHistory);
        $this->em->flush();

        return $event;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernel::MASTER_REQUEST != $event->getRequestType()) {
            return;
        }

        if ($event->getRequest()->get('_route') == $this->changePasswordRoute || '_assetic' == substr($event->getRequest()->get('_route'), 0, 8)) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return;
        }

        if ('' != $this->avoidRole && $this->authorizationChecker->isGranted($this->avoidRole)) {
            return;
        }

        $user = $token->getUser();

        if (!$user) {
            return;
        }

        $lastUserPassword = $this->em->getRepository('ACSEOChangePasswordBundle:PasswordHistory')->findOneBy(array('user' => $user), array('createdAt' => 'DESC'), 1);

        if (!$lastUserPassword) {
            return;
        }

        $lastPasswordDate = $lastUserPassword->getCreatedAt();

        if ($lastPasswordDate->add(new \DateInterval($this->passwordExpireAfter)) < new \Datetime()) {
            if ($this->enableFlashbagMessage) {
                $event->getRequest()->getSession()->getFlashBag()->add('danger', 'Votre mot de passe a expiré, vous devez en saisir un nouveau');
            }
            $response = new RedirectResponse($this->router->generate($this->changePasswordRoute));
            $event->setResponse($response);
        }
    }
}

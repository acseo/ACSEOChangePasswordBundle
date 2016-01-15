<?php

namespace ACSEO\ChangePasswordBundle\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Routing\Router;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Model\UserInterface;
use Doctrine\ORM\EntityManager;
use ACSEO\ChangePasswordBundle\Entity\PasswordHistory;

class ChangePasswordListener implements EventSubscriberInterface
{

    public function __construct(EntityManager $em, SecurityContext $security, Router $router, $passwordExpireAfter)
    {
        $this->em = $em;
        $this->security = $security;
        $this->router = $router;
        $this->passwordExpireAfter = $passwordExpireAfter;
        $this->changePasswordRoute = $changePasswordRoute;
    }

    public static function getSubscribedEvents()
    {
        return array (FOSUserEvents::CHANGE_PASSWORD_COMPLETED => 'onChangePasswordCompleted',
                      FOSUserEvents::REGISTRATION_COMPLETED    => 'onChangePasswordCompleted',
                      FOSUserEvents::RESETTING_RESET_COMPLETED   => 'onChangePasswordCompleted',
                      KernelEvents::REQUEST                    => 'onKernelRequest',
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

        if ($event->getRequest()->get("_route") == $this->changePasswordRoute || substr($event->getRequest()->get("_route"), 0, 8) == "_assetic") {
            return;
        }

        $token = $this->security->getToken();

        if (!$token) {
            return;
        }

        $user = $token->getUser();

        if (!$user) {
            return;
        }

        $lastUserPassword = $this->em->getRepository("ACSEOChangePasswordBundle:PasswordHistory")->findOneBy(array("user" =>$user), array("createdAt" => "DESC"), 1);

        if (!$lastUserPassword) {
            return;
        }

        $lastPasswordDate = $lastUserPassword->getCreatedAt();

        if ($lastPasswordDate->add(new \DateInterval($this->passwordExpireAfter)) < new \Datetime()) {
            $response = new RedirectResponse($this->router->generate($this->changePasswordRoute));
            $event->setResponse($response);
        }


    }
}

<?php

namespace ACSEO\ChangePasswordBundle\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Doctrine\UserManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class NotInPreviousPasswordsValidator extends ConstraintValidator
{
    public function __construct(EntityManager $em, TokenStorageInterface $tokenStorage, RequestStack $requestStack, UserManager $fosUserManager)
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
        $this->fosUserManager = $fosUserManager;
    }

    public function validate($value, Constraint $constraint)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null != $request->get('token', null)) {
            $user = $this->fosUserManager->findUserByConfirmationToken($request->get('token'));
        } else {
            $user = $this->tokenStorage->getToken()->getUser();
        }

        if (!$user) {
            return;
        }

        try {
            $oldPasswords = $this->em->getRepository('ACSEOChangePasswordBundle:PasswordHistory')->findBy(
                ['user' => $user],
                ['createdAt' => 'DESC'],
                $constraint->getHistoryDepth()
            );

            foreach ($oldPasswords as $oldPassword) {
                if (password_verify($value, $oldPassword->getPassword())) {
                    $this->context->addViolation($constraint->message);

                    break;
                }
            }
        } catch (\Exception $e) {
            // Known case : No encoder has been configured for account "anon.". for user Reset action
            return;
        }
    }
}

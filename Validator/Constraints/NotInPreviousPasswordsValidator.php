<?php

namespace ACSEO\ChangePasswordBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Routing\Router;
use Doctrine\ORM\EntityManager;

class NotInPreviousPasswordsValidator extends ConstraintValidator
{

    public function __construct( EntityManager $em, SecurityContext $security, $encoder_service, RequestStack $requestStack, UserManager $fosUserManager)
    {   
        $this->em = $em;
        $this->security = $security;
        $this->encoder_service = $encoder_service;
        $this->requestStack = $requestStack;
        $this->fosUserManager = $fosUserManager;

    }   


    public function validate($value, Constraint $constraint)
    {   
        $request = $this->requestStack->getCurrentRequest();
        if (null != $request->get("token", null)) {
            $user = $this->fosUserManager->findUserByConfirmationToken($request->get("token"));
        }   
        else {
            $user = $this->security->getToken()->getUser();
        }   

        if (!$user) {
            return;
        }   

        try {
            $encoder = $this->encoder_service->getEncoder($user);
            $encodedValue = $encoder->encodePassword($value, $user->getSalt());

            $passwordMatch= $this->em->getRepository("ACSEOChangePasswordBundle:PasswordHistory")
                ->findBy(array("user" =>$user, "password" => $encodedValue )); 

            if(sizeof($passwordMatch) != 0) {   
                $this->context->addViolation($constraint->message);
            }   
        }   
        catch (\Exception $e) {
            // Known case : No encoder has been configured for account "anon.". for user Reset action
            return;
        }   
    }   
}

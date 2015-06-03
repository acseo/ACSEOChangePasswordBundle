<?php
namespace ACSEO\ChangePasswordBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class NotInPreviousPasswords extends Constraint
{
    public $message = 'Ce mot de passe a déjà été utilisé dans le passé';


    public function validatedBy()
    {
        return 'acseo.validator.notinpreviouspasswords';
    }

}

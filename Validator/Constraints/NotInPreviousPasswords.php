<?php

namespace ACSEO\ChangePasswordBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class NotInPreviousPasswords extends Constraint
{
    const DEFAULT_DEPTH = -1;

    public $message = 'Ce mot de passe a déjà été utilisé dans le passé';

    public function validatedBy()
    {
        return \get_class($this).'Validator';
    }

    /** @var int|null */
    protected $historyDepth;

    /**
     * {@inheritdoc}
     */
    public function __construct($options = null)
    {
        $this->historyDepth = isset($options['historyDepth']) ? $options['historyDepth'] : null;

        parent::__construct($options);
    }

    /**
     * @return int|null
     */
    public function getHistoryDepth()
    {
        return $this->historyDepth;
    }
}

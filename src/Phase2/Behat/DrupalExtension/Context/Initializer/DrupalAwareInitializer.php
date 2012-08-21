<?php

namespace Phase2\Behat\DrupalExtension\Context\Initializer;

use Behat\Behat\Context\Initializer\InitializerInterface;
use Behat\Behat\Context\ContextInterface;

use Phase2\Behat\DrupalExtension\Drupal;
use Phase2\Behat\DrupalExtension\Context\DrupalAwareInterface;

/**
 * Drupal aware contexts initializer.
 * Sets Drupal alias on the DrupalAware contexts.
 */
class DrupalAwareInitializer implements InitializerInterface
{
    private $drupal;

    /**
     * Initializes initializer.
     *
     * @param string $alias
     */
    public function __construct(Drupal $drupal)
    {
        $this->drupal = $drupal;
    }

    /**
     * Checks if initializer supports provided context.
     *
     * @param ContextInterface $context
     *
     * @return Boolean
     */
    public function supports(ContextInterface $context)
    {
        // if context/subcontext implements DrupalAwareInterface
        if ($context instanceof DrupalAwareInterface) {
            return true;
        }

        return false;
    }

    /**
     * Initializes provided context.
     *
     * @param ContextInterface $context
     */
    public function initialize(ContextInterface $context)
    {
        $context->setDrupal($this->drupal);
    }
}

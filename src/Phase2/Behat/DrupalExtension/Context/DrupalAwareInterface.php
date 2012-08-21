<?php

namespace Phase2\Behat\DrupalExtension\Context;

use Phase2\Behat\DrupalExtension\Drupal;

/**
 * Drush aware interface for contexts.
 */
interface DrupalAwareInterface
{
    /**
     * Sets the Drupal service instance.
     *
     * @param Drupal $drupal Drupal service instance.
     */
    public function setDrupal(Drupal $drupal);
}
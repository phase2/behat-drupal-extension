<?php

namespace Phase2\Behat\DrupalExtension\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Behat\Behat\Event\ScenarioEvent,
    Behat\Behat\Event\OutlineEvent;

/**
 * This class does 2 important things.
 * 1. chdir into the DRUPAL_ROOT before each scenario.
 * 2. chdir back to the previous directory after each scenario.
 *
 * The chdir steps are required because even after setting DRUPAL_ROOT, Drupal
 * is not completely safe to run from outside of DRUPAL_ROOT, specifically there
 * are issues regarding drupal_system_listing() which doesn't use DRUPAL_ROOT
 * when searching for system files.
 *
 * Likewise, the Behat console app relies on the current directory for feature
 * loading, so we must delay changing directories until the last possible moment.
 *
 * If ever Drupal does not require this chdir(), then this listener can be
 * removed completely.
 */
class DrupalRootListener implements EventSubscriberInterface
{
    private $pwd_stack = array();

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return array(
            'beforeScenario' => array('prepareDrupal', 10),
            'beforeOutline'  => array('prepareDrupal', 10),
            'afterScenario'  => array('teardownDrupal', -10),
            'afterOutline'   => array('teardownDrupal', -10)
        );
    }

    /**
     * Prepares Drupal, bootstrapping if necessary.
     *
     * @param ScenarioEvent|OutlineEvent $event
     */
    public function prepareDrupal($event)
    {
        $scenario = $event instanceof ScenarioEvent ? $event->getScenario() : $event->getOutline();

        // chdir to DRUPAL_ROOT to get around Drupal issues.
        $this->pushd(DRUPAL_ROOT);
    }

    /**
     * Restore the working directory after each scenario.
     *
     * @param ScenarioEvent|OutlineEvent $event
     */
    public function teardownDrupal($event)
    {
        $scenario = $event instanceof ScenarioEvent ? $event->getScenario() : $event->getOutline();

        // Go back to the original directory.
        $this->popd();
    }

    /**
     * Change directories, storing the previous directory for later.
     */
    protected function pushd($dir) {
      array_push($this->pwd_stack, getcwd());
      chdir($dir);
    }

    /**
     * Change directories to the previous one.
     */
    protected function popd() {
      $dir = array_pop($this->pwd_stack);
      if (!empty($dir)) {
        chdir($dir);
      }
    }
}

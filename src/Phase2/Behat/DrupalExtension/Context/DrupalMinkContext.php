<?php

namespace Phase2\Behat\DrupalExtension\Context;

use Phase2\Behat\DrupalExtension\Context\DrupalContext;

use Behat\MinkExtension\Context\MinkContext;

/**
 * Drupal aware Mink Context.
 */
class DrupalMinkContext extends DrupalContext
{
    /**
     * Current authenticated user.
     *
     * A value of FALSE denotes an anonymous user.
     */
    protected $loggedInUser = FALSE;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context object.
     *
     * @param array $parameters.
     *   Context parameters (set them up through behat.yml or behat.local.yml).
     */
    public function __construct(array $parameters) {
      $this->useContext('mink', new MinkContext($parameters));
    }

    /**
     * Forward undefined calls to the subcontexts.  First come first served.
     */
    public function __call($name, $arguments) {
      foreach ($this->getSubcontexts() as $subcontext) {
        if (in_array($name, get_class_methods($subcontext))) {
          return call_user_func_array(array($subcontext, $name), $arguments);
        }
      }
    }

    /**
     * Logs out the current user, if logged in.
     *
     * @Given /^I am an anonymous user$/
     * @Given /^I am logged out$/
     */
    public function logout() {
      // Verify the user is logged out.
      if ($this->loggedInUser) {
        $this->visit('/user/logout');
        $this->loggedInUser = FALSE;
      }
    }

    /**
     * Authenticates a user in the current session.
     *
     * @Given /^I am logged in as (a user with the "[^"]*" role)$/
     * @Given /^I am logged in as ("[^"]*" with the password "[^"]*")$/
     *
     * @param $account
     *   A fully loaded user object with the plain text password in the "pass_raw" property.
     */
    public function login($account) {
      // Check if logged in.
      if ($this->loggedInUser && ($this->loggedInUser->name != $account->name)) {
        $this->logout();
      }

      $this->visit('/user');

      try {
        $element = $this->assertSession()->elementExists('css', '#user-login');
      }
      catch (\Exception $e) {
        $this->showLastResponse();
        throw $e;
      }

      $element->fillField('edit-name', $account->name);
      $element->fillField('edit-pass', $account->pass_raw);
      $submit = $element->findButton('Log in');
      if (empty($submit)) {
        throw new \Exception('No submit button at ' . $this->getSession()->getCurrentUrl());
      }

      // Log in.
      $submit->click();

      // If a logout link is found, we are logged in. While not perfect, this is
      // how Drupal SimpleTests currently work as well.
      if (!$this->getSession()->getPage()->findLink('Log out')) {
        throw new \Exception("Failed to log in as user \"{$account->name}\".");
      }

      $this->loggedInUser = $account;
    }

}

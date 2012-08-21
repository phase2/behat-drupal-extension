<?php

namespace Phase2\Behat\DrupalExtension\Context;

use Phase2\Behat\DrupalExtension\Drupal;

use Behat\Behat\Context\BehatContext;

/**
 * Raw Drupal context for Behat BDD tool.
 * Provides raw Drupal integration (without step definitions) and web assertions.
 */
class DrupalContext extends BehatContext implements DrupalAwareInterface
{
    private $drupal;

    /**
     * This array contains all of the user accounts created by this context.
     */
    protected $users = array();

    /**
     * Sets Drupal instance.
     *
     * @param string $drupal
     */
    public function setDrupal(Drupal $drupal)
    {
        $this->drupal = $drupal;
    }

    /**
     * Returns Drupal instance.
     *
     * @return string
     */
    public function getDrupal()
    {
        return $this->drupal;
    }

    /**
     * Create a user with a given role.
     *
     * @Transform /^a user with the "([^"]*)" role$/
     *
     * @param string $role
     *   Name of the role to assign to user.
     *
     * @return object|false
     *   A fully loaded user object with pass_raw property.
     *
     * @throws \RuntimeException
     */
    public function createUserWithRole($role) {
      return $this->createUserWithRoles(array($role));
    }

    /**
     * @Given /^There is a user named "([^"]*)" with the password "([^"]*)"$/
     * @Transform /^"([^"]*)" with the password "([^"]*)$"/
     */
    public function createUserWithNameAndPassword($username, $password) {
      $account = user_load_by_name($username);
      if (empty($account)) {
        $account = $this->createUser(array('name' => $username, 'pass' => $password));
      }
      else {
        $account = user_save($account, array('pass' => $password));
        $account->pass_raw = $password;
      }

      return $account;
    }

    /**
     * Create a user with a given set of roles.
     *
     * @param array $roles
     *   Array of role names to assign to user.
     *
     * @return object|false
     *   A fully loaded user object with pass_raw property.
     *
     * @throws \RuntimeException
     */
    public function createUserWithRoles(array $roles = array()) {
      $rids = array();
      foreach ($roles as $name) {
        if (!($role = user_role_load_by_name($name))) {
          throw new \Exception(sprintf('A role named "%s" was not found.', $name));
        };
        $rids[$role->rid] = $role->rid;
      }

      return $this->createUser(array('roles' => $rids));
    }

    /**
     * Create a user with a given set of attributes.
     *
     * @param array $edit
     *   Array of user attributes.  See user_save() for more info.
     *
     * @return object|false
     *   A fully loaded user object with pass_raw property.
     *
     * @throws \RuntimeException
     */
    public function createUser(array $edit = array()) {
      if (empty($edit['name'])) {
        $edit['name'] = user_password();
      }

      if (empty($edit['mail'])) {
        $edit['mail'] = $edit['name'] . '@example.com';
      }

      if (empty($edit['pass'])) {
        $edit['pass'] = user_password();
      }

      if (!isset($edit['status'])) {
        $edit['status'] = 1;
      }

      $account = user_save(drupal_anonymous_user(), $edit);

      if (empty($account->uid)) {
        throw new \RuntimeException(sprintf('Unable to create account with name "%s" and password "%s".', $edit['name'], $edit['pass']));
      }

      // Add the raw password so that we can use it to log in as this user.
      $account->pass_raw = $edit['pass'];

      $this->users[] = $account;

      return $account;
    }

    /**
     * Remove users created during this scenario, including the content they created.
     *
     * @AfterScenario
     */
    public function removeTestUsers($event) {
      // Remove any users that were created.
      if (!empty($this->users)) {
        foreach ($this->users as $account) {
          user_cancel(array(), $account->uid, 'user_cancel_delete');

          // I got the following technique here: http://drupal.org/node/638712
          $batch =& batch_get();
          $batch['progressive'] = FALSE;
          batch_process();
        }
      }
    }

    /**
     * @Given /^cron has been run$/
     */
    public function cronHasBeenRun() {
      $this->getDrupal()->cron();
    }

}

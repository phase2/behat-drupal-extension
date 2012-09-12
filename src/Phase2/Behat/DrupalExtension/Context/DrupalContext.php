<?php

namespace Phase2\Behat\DrupalExtension\Context;

use Behat\Behat\Context\BehatContext;
use Behat\Gherkin\Node\TableNode;

/**
 * Raw Drupal context for Behat BDD tool.
 * Provides raw Drupal integration (without step definitions) and web assertions.
 */
class DrupalContext extends BehatContext
{
    /**
     * This array contains all of the user accounts created by this context.
     */
    protected $users = array();

    /**
     * This array contains all of the testing modules installed by this context.
     */
    protected $modules = array();

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
      return;
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

    /**
     * How many items are need to be indexed?
     */
    protected function searchIndexRemaining() {
      $remaining = 0;
      $total = 0;
      foreach (variable_get('search_active_modules', array('node', 'user')) as $module) {
        if ($status = module_invoke($module, 'search_status')) {
          $remaining += $status['remaining'];
          $total += $status['total'];
        }
      }

      return $remaining;
    }

    /**
     * @Given /^the search index is updated$/
     */
    public function searchIndexIsUpdated($max_passes = 3) {
      for ($i = 0; $i < $max_passes, $this->searchIndexRemaining() > 0; $i++) {
        $this->getDrupal()->cron();
      }

      $remaining = $this->searchIndexRemaining();
      if ($remaining > 0) {
        throw new \RuntimeException('Search index queue has %d remaining items after %d passes.', $remaining, $max_passes);
      }
    }

    /**
     * Create a random string for use as titles, user names, etc.
     */
    protected function randomName($length = 8) {
      return user_password($length);
    }

    /**
     * @Transform /^table:node setting,value$/
     */
    public function castNodeSettingsTableToArray(TableNode $table) {
      $settings = $table->getRowsHash();
      unset($settings['node setting']);
      return $settings;
    }

    /**
     * @Given /^there is a node with the following settings$/
     */
    public function createNode(array $settings) {
      // Populate defaults array.
      $settings += array(
        'body' => array(LANGUAGE_NONE => array(array())),
        'title' => $this->randomName(8),
        'comment' => 2,
        'changed' => REQUEST_TIME,
        'moderate' => 0,
        'promote' => 0,
        'revision' => 1,
        'log' => '',
        'status' => 1,
        'sticky' => 0,
        'type' => 'page',
        'revisions' => NULL,
        'language' => LANGUAGE_NONE,
      );

      // Use the original node's created time for existing nodes.
      if (isset($settings['created']) && !isset($settings['date'])) {
        $settings['date'] = format_date($settings['created'], 'custom', 'Y-m-d H:i:s O');
      }

      // If the node's user uid is not specified manually, use the currently
      // logged in user if available, or else the user running the test.
      if (!isset($settings['uid'])) {
        if ($this->loggedInUser) {
          $settings['uid'] = $this->loggedInUser->uid;
        }
        else {
          global $user;
          $settings['uid'] = $user->uid;
        }
      }

      // Merge body field value and format separately.
      $body = array(
        'value' => $this->randomName(32),
        'format' => filter_default_format(),
      );
      if (!isset($settings['body'][$settings['language']])) {
        $settings['body'][$settings['language']] = array(0 => $body);
      }
      else {
        $settings['body'][$settings['language']][0] += $body;
      }

      $node = (object) $settings;
      node_save($node);

      if (!$node->nid) {
        throw new \RuntimeException('Unable to create node.');
      }

      // Small hack to link revisions to our test user.
      db_update('node_revision')
          ->fields(array('uid' => $node->uid))
          ->condition('vid', $node->vid)
          ->execute();

      return $node;
    }

    /**
     * @Given /^the "([^"]*)" module is installed$/
     */
    public function assertModuleExists($module, $message = NULL, $install = TRUE ) {
      if (module_exists($module)) {
        return TRUE;
      }

      if ($install && module_enable(array($module))) {
        $this->modules[] = $module;
        return TRUE;
      }

      if (empty($message)) {
        $message = sprintf('Module "%s" is not installed.', $module);
      }
      throw new \RuntimeException($message);
    }

    /**
     * @AfterScenario
     */
    public function afterScenarioRemoveModules($event) {
      module_disable($this->modules);
    }

}

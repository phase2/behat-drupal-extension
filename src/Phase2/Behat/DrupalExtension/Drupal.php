<?php

namespace Phase2\Behat\DrupalExtension;

/**
 * Drupal service for bootstrapping and resetting a Drupal site.
 */
class Drupal {

  private $drupal_root;
  private $base_url;

  public function __construct($drupal_root, $base_url = NULL) {
    $this->setDrupalRoot($drupal_root);
    $this->setBaseUrl($base_url);
  }

  /**
   * Set the Drupal root.
   *
   * @param $drupal_root
   *   The path to the Drupal document root.  This can be absolute or relative
   *   to the Behat root.
   */
  public function setDrupalRoot($drupal_root) {
    $this->drupal_root = realpath($drupal_root);
  }

  /**
   * Get the Drupal root.
   */
  public function getDrupalRoot() {
    return $this->drupal_root;
  }

  /**
   * Set the Drupal base URL.
   *
   * @param $base_url
   *   The URL to be used when bootstrapping Drupal.  If using MinkExtension,
   *   this should match the base_url setting used for the MinkExtension.
   */
  public function setBaseUrl($base_url) {
    $this->base_url = $base_url;
  }

  /**
   * Get the Drupal base URL.
   */
  public function getBaseUrl() {
    return $this->base_url;
  }

  /**
   * Bootstrap Drupal based with the given Drupal root and base URL.
   *
   * @throws \RuntimeException
   */
  public function bootstrap() {
    if (defined('DRUPAL_ROOT')) {
      throw new \RuntimeException('Drupal cannot be bootstrapped more than once.');
    }

    define('DRUPAL_ROOT', $this->drupal_root);
    require_once DRUPAL_ROOT . '/includes/bootstrap.inc';

    drupal_override_server_variables(array('url' => $this->base_url));
    drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
  }

  /**
   * Reset all data structures after having enabled new modules.
   *
   * This method is called by DrupalWebTestCase::setUp() after enabling
   * the requested modules. It must be called again when additional modules
   * are enabled later.
   *
   * Borrowed from DrupalWebTestCase.
   */
  public function resetAll() {
    // Reset all static variables.
    drupal_static_reset();
    // Reset the list of enabled modules.
    module_list(TRUE);

    // Reset cached schema for new database prefix. This must be done before
    // drupal_flush_all_caches() so rebuilds can make use of the schema of
    // modules enabled on the cURL side.
    drupal_get_schema(NULL, TRUE);

    // Perform rebuilds and flush remaining caches.
    drupal_flush_all_caches();

    // Reload global $conf array.
    $this->refreshVariables();
  }

  /**
   * Refresh the in-memory set of variables. Useful after a page request is made
   * that changes a variable in a different thread.
   *
   * In other words calling a settings page with $this->drupalPost() with a changed
   * value would update a variable to reflect that change, but in the thread that
   * made the call (thread running the test) the changed variable would not be
   * picked up.
   *
   * This method clears the variables cache and loads a fresh copy from the database
   * to ensure that the most up-to-date set of variables is loaded.
   *
   * Borrowed from DrupalWebTestCase.
   */
  protected function refreshVariables() {
    global $conf;
    cache_clear_all('variables', 'cache_bootstrap');
    $conf = variable_initialize();
  }

  /**
   * Execute the Drupal cron tasks.  This wrapper for drupal_run_cron() exists
   * because of an issue with drupal_system_listing() that doesn't use the
   * DRUPAL_ROOT when searching for module files.  So as to not interfere with
   * anything else running in this process, we temporarily change directories
   * just for the execution of this cron run.
   */
  public function cron() {
    $cwd = getcwd();
    chdir(DRUPAL_ROOT);
    $return = @drupal_cron_run();
    chdir($cwd);
    return $return;
  }
}


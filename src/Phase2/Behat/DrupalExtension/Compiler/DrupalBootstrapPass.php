<?php

namespace Phase2\Behat\DrupalExtension\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Drupal Bootstrap initialization pass.
 */
class DrupalBootstrapPass implements CompilerPassInterface
{
    /**
     * Bootstraps Drupal.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter('behat.drupal.drupal_root')) {
          return;
        }

        // Do the bootstrap.
        $this->bootstrap($container->getParameter('behat.drupal.drupal_root'), $container->getParameter('behat.drupal.base_url'));

        // if a module name specified - direct behat.paths.features to it
        if ($module = $container->getParameter('behat.drupal.module')) {
            $module_path = drupal_get_path('module', $module);
            if (empty($module_path)) {
              throw new \RuntimeException(sprintf('The module "%s" could not be found.', $module));
            }

            $module_info = system_get_info('module', $module);

            // Module-relative path to features directory can be set in the module
            // info file.  Defaults to "features".
            if (!empty($module_info['behat']['features'])) {
              $feature_path = $module_info['behat']['features'];
            }
            else {
              $feature_path = 'features';
            }

            // Module-relative path to bootstrap directory can be set in the
            // module info file.  Defaults to <feature_path>/bootstrap.
            if (!empty($module_info['behat']['bootstrap'])) {
              $bootstrap_path = $module_info['behat']['bootstrap'];
            }
            else {
              $bootstrap_path = $feature_path . '/bootstrap';
            }

            $container->setParameter(
                'behat.paths.features',
                DRUPAL_ROOT . "/{$module_path}/{$feature_path}"
            );

            $container->setParameter(
                'behat.paths.bootstrap',
                DRUPAL_ROOT . "/{$module_path}/{$bootstrap_path}"
            );
        }
    }

    /**
     * Bootstrap Drupal.
     */
    protected function bootstrap($drupal_root, $base_url) {
        // Only bootstrap Drupal once.
        if (!defined('DRUPAL_ROOT')) {
            define('DRUPAL_ROOT', $drupal_root);

            // chdir to DRUPAL_ROOT to get around Drupal issues.
            $old_pwd = getcwd();
            chdir(DRUPAL_ROOT);

            // bootstrap Drupal
            require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
            drupal_override_server_variables(array(
              'url' => $base_url,
            ));
            drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

            // Go back to the original directory.
            chdir($old_pwd);
        }
    }

}
<?php

namespace Phase2\Behat\DrupalExtension\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Kernel initialization pass.
 * Loads kernel file and initializes kernel.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
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

        // drupal_root can be absolute or relative to behat.paths.base
        $drupal_root = $container->getParameter('behat.drupal.drupal_root');
        if (strpos($drupal_root, DIRECTORY_SEPARATOR) !== 0) {
          $base_path = $container->getParameter('behat.paths.base');
          $drupal_root = realpath($base_path . DIRECTORY_SEPARATOR . $drupal_root);
        }

        // Even after setting DRUPAL_ROOT, we still need to chdir() because of
        // an issue with drupal_system_listing() that doesn't use the DRUPAL_ROOT
        // when searching for module files.
        define('DRUPAL_ROOT', $drupal_root);
        chdir(DRUPAL_ROOT);

        // bootstrap Drupal
        require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
        drupal_override_server_variables(array(
          'url' => $container->getParameter('behat.drupal.base_url'),
        ));
        drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

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
}
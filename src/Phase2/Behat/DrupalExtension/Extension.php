<?php

namespace Phase2\Behat\DrupalExtension;

use Symfony\Component\Config\FileLocator,
    Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Behat\Behat\Extension\Extension as BaseExtension;

/**
 * Drush extension for Behat class.
 */
class Extension extends BaseExtension {

  /**
   * Loads a specific configuration.
   *
   * @param array $config Extension configuration hash (from behat.yml)
   * @param ContainerBuilder $container ContainerBuilder instance
   */
  public function load(array $config, ContainerBuilder $container) {
    $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/services'));
    $loader->load('services.xml');

    if (isset($config['drupal_root'])) {
      $container->setParameter('behat.drupal.drupal_root', $config['drupal_root']);
    }
    if (isset($config['base_url'])) {
      $container->setParameter('behat.drupal.base_url', $config['base_url']);
    }

    $container->get('behat.drupal.drupal')->bootstrap();
  }

}

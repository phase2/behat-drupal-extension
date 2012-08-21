<?php

spl_autoload_register(function($class) {
  if (false !== strpos($class, 'Phase2\\Behat\\DrupalExtension')) {
    require_once(__DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php');
    return true;
  }
}, true, false);

return new Phase2\Behat\DrupalExtension\Extension;
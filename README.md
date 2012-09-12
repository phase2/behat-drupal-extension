DrupalExtension
---

This extension provides Behat integration with Drupal CMS projects.  It provides:

* DrupalMinkContext, which provides some useful step definitions for common
  Drupal functions, such as creating users and logging in.

Installation
---

This extension requires:

* [Behat 2.4+](http://behat.org/)
* [MinkExtension](http://extensions.behat.org/mink/)

### Through Composer

1. Define dependencies in your composer.json:
```javascript
   {
       "require": {
           ...
           "phase2/drupal-extension": "*"
       },
       "repositories": [
         ...
         {
           "type": "vcs",
           "url": "https://github.com/phase2/behat-drupal-extension"
         }
       ]
   }
```

2. Install/update your vendors
```
   $ curl http://getcomposer.org/installer | php
   $ php composer.phar install
```
3. Activate extension in your behat.yml
```yml
   default:
     # ...
       extensions:
         Phase2\Behat\DrupalExtension\Extension:
           drupal_root: /path/to/drupal
           base_url: http://example.com/
```

Usage
---

After installing extension, there are 2 usage options available for you:

1. Set the context class in the configuration file to use DrupalMinkContext.

```yml
   # behat.yml
   default:
     context:
       class: Phase2\Behat\DrupalExtension\Context\DrupalMinkContext
```

   This will give you access to all of the pre-defined Drupal and Mink steps
   without needing to create a FeatureContext of your own.

2. Extend `Phase2\Behat\DrupalExtension\Context\DrupalMinkContext` with your context
   or subcontext if you need additional step definitions or hooks.

Configuration
---

DrupalExtension comes with a flexible configuration system, that gives you the
ability to configure how Drupal is used.

* `drupal_root` - specifies the path to your Drupal document root.
* `base_url` - specify the URL to be used when bootstrapping Drupal.  If using
  MinkExtension, this should match the base_url in the MinkExtension settings.
* `module` - specify the name of an enabled Drupal module to run the feature
  suite bundled with the module.

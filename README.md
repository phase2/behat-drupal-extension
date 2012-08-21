DrupalExtension
---

This extension provides Behat integration with Drupal CMS projects.  It provides:

* DrupalAwareInterface, which provides a Drupal service for your contexts.  This
  Drupal service can bootstrap and refresh Drupal between scenarios.
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
           "url": "https://github.com/phase2/DrupalExtension"
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

1. Implementing `Phase2\Behat\DrupalExtension\Context\DrupalAwareInterface` with your
   context or its subcontexts. This will give you the flexibility to inherit
   from any Context object, but also get an initialized Drupal service set in
   your context.
2. Extend `Phase2\Behat\DrupalExtension\Context\DrupalMinkContext` with your context or
   subcontext.  This context is an implementation of the `DrupalAwareInterface`
   and also and extension of `MinkContext`.  You will get all of the Mink step
   definitions, all of the Drupal step definitions, and access to an initialized
   Drupal service.

Both of these methods will implement the `setDrupal(Drupal $drupal)` method. This
method would be automatically called immediately after each context creation
before each scenario, initialized with the parameters set in your `behat.yml` file.

Configuration
---

DrupalExtension comes with a flexible configuration system, that gives you the
ability to configure how Drupal is used.

* `drupal_root` - specifies the path to your Drupal document root.
* `base_url` - specify the URL to be used when bootstrapping Drupal.  If using
  MinkExtension, this should match the base_url in the MinkExtension settings.

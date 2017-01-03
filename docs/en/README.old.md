
ecommerce delivery ELECTRONICALLY
================================================================================

Allows you to sell a product that is (or includes) downloads from the site.

You can also add files to the Order Step that will be added to any download.

Here are the steps:


Developers
-----------------------------------------------
Nicolaas Francken [at] sunnysideup.co.nz



Documentation
-----------------------------------------------
Please contact author for more details.

Any bug reports and/or feature requests will be
looked at

We are also very happy to provide personalised support
for this module in exchange for a small donation.


Requirements
-----------------------------------------------
see composer.json


Project Home
-----------------------------------------------
See http://www.silverstripe-ecommerce.com

Demo
-----------------------------------------------
See http://www.silverstripe-ecommerce.com


Installation Instructions
-----------------------------------------------

1. Find out how to add modules to SS and add module as per usual.

2. Review configs and add entries to mysite/_config/config.yml
(or similar) as necessary.
In the _config/ folder of this module
you can usually find some examples of config options (if any).

The main thing here is that you add an orderstep in your config files.

See [example config file](_config/EcommerceDeliveryElectronic.yml.example) for an example.

Without adding that order step you will not be able to use this module.

All other settings are optional.

If you like the old download folder to be cleaned up you have two options:

 (a) set up a cron job (you will need to know how to setup cron jobs) running the following code:

```php
    php framework/cli-script.php /my-website-root-goes-here/dev/tasks/ElectronicDownloadProductCleanUp
```

 (b) add the following code to your Page_Controller class (THIS IS A HACK!!!)

```php
    function init(){
      parent::init();
      //set frequency - currently set to 1 in 10...
      if(rand(1,10) == 5) {
        $task = new ElectronicDownloadProductCleanUp();
        $task->run(null);
      }
    }
```


# CiviCRM Entity for Drupal 8

## Composer Installation

Due to a [bug](https://www.drupal.org/project/project_composer/issues/3051746) with Drupal.org's composer facade. It's not possible to simply `composer require drupal/civicrm_entity` without some preparation.

To get CiviCRM Entity installed with composer the following steps will work:
    
1. Add the CiviCRM Entity repository to your composer.json:  
    `composer config repositories.civicrm_entity vcs https://github.com/eileenmcnaughton/civicrm_entity`
2. Require CiviCRM Entity's `8.x-3.x` git branch:  
    `composer require drupal/civicrm_entity:dev-8.x-3.x`
    
**Note:**

You should ensure that your `composer.json` file has versioned requirements for both `civicrm/civicrm-core` and `civicrm/civicrm-drupal-8`.

For example:

``` json
       "civicrm/civicrm-core": "^5.19",
       "civicrm/civicrm-drupal-8": "^5.19",
```

By default the **RoundEarth** method uses `dev-master` for `civicrm/civicrm-drupal-8` - this is [dangerous and not recommended](https://lab.civicrm.org/dev/drupal/issues/87#note_23534)!

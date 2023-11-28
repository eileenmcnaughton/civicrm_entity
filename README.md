# CiviCRM Entity for Drupal 10

## Installing 4.0.0-alpha7 or later.

This module is installable with normal Drupal composer install steps, `composer require drupal/civicrm_entity`.
    
**Note:**

You should ensure that your `composer.json` file has versioned requirements for both `civicrm/civicrm-core` and `civicrm/civicrm-drupal-8`.

For example:

``` json
       "civicrm/civicrm-core": "^5.63",
       "civicrm/civicrm-packages": "^5.63",
       "civicrm/civicrm-drupal-8": "^5.63",
```
## CiviCRM Entity Leaflet upgrade to Drupal 10

In the 4.0.x version the civicrm_entity_leaflet module has been removed from the main module repo and put into its own module project http://drupal.org/project/civicrm_entity_leaflet

If upgrading from Drupal 9 to Drupal 10, simply include that module:
`composer require drupal/civicrm_entity_leaflet`

## Version support

8.x-3.x for Drupal 9 Known to work with CiviCRM 5.51+
4.0.x for Drupal 10 Requires CiviCRM 5.60+

We do our best to support as many versions of CiviCRM Core as is feasible. 

CiviCRM Entity is primarily tested and developed with a focus on Extended Support Release (ESR) versions of CiviCRM. Updates needed due to CiviCRM Core are typically driven by reported issues or contributed changes. While it's uncommon for a newer CiviCRM version to encounter issues, for best success use the ESR version.
https://civicrm.org/esr

## Issues

For bug reports, support requests, or feature requests please create an issue in the Drupal.org issue queue https://www.drupal.org/project/issues/civicrm_entity


## Contribution

Primary development happens in the github repo: https://github.com/eileenmcnaughton/civicrm_entity

Please make PRs against the 4.0.x branch first. Changes will be merged there, and then backported to the 8.x-3.x branch.

We do plan on shifting primary development to the drupal.org Gitlab infrastructure in time.


### Develop with the most recent version versions.

To get CiviCRM Entity github repo installed with composer the following steps will work:
    
1. Add the CiviCRM Entity repository to your composer.json:  
    `composer config repositories.civicrm_entity vcs https://github.com/eileenmcnaughton/civicrm_entity`
2. Require CiviCRM Entity's `4.0.x` git branch:  
    `composer require drupal/civicrm_entity:dev-4.0.x`

## Get support now

CiviCRM Entity is open source software. Its support and improvement depends upon the good will and contribution of open source developers' time and the investment of money by individuals and organizations.

We do our best to answer requests and fix bugs, and are committed to continuing development and supporting the module as both Drupal Core and CiviCRM evolve.

Primary development is managed by [Skvare](https://skvare.com), but a community of developers supports and contributes to the module.

If you or your organization has specific or immediate needs, or simply wishes to support the continued development and maintenance of CiviCRM Entity you can [Contact Skvare](https://skvare.com/contact) and our dedicated team of account managers, business analysts, project managers, and developers will work to get the solution you need, as fast as we can.

## Developers

[Mark Hanna](https://www.drupal.org/u/markusa), Architect and Senior Developer at Skvare, module maintainer.

[Arnold French](https://www.drupal.org/u/dsdeiz), Developer at Skvare

[Eileen Mcnaughton](https://github.com/eileenmcnaughton) Original creator of CiviCRM Entity, and tireless contributor to CiviCRM core and a multitude of CiviCRM Extensions

[Matt Glaman](https://www.drupal.org/u/mglaman) Initial development of the Drupal 8 version was a massive contribution.

[Jitendra Purohit](https://www.drupal.org/u/jitendrapurohit) Developer and contributor

See all contributors: https://github.com/eileenmcnaughton/civicrm_entity/graphs/contributors

## Supporting Organizations

[Skvare](https://skvare.com) Skvare Supporting and managing CiviCRM since 2008. [Every Skvare team member](https://skvare.com/about) has a hand in CiviCRM Entity's continued development.

[Fuzion](https://www.drupal.org/fuzion) Founding organization of the initial versions of CiviCRM Entity, and steady support and contribution throughout the years.

[SemperIT](https://semper-it.com/) Sponsors contribution from D8 onwards

[CiviCRM Core Team](https://civicrm.org/about/core-team) For creating CiviCRM, and supporting us all and Drupal integration in general.

[MyDropWizard](https://www.drupal.org/mydropwizard) Original funding for the Drupal 8 version, and the composerization of CiviCRM

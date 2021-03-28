Site Studio Reporting
====

This is a module providing drush commands that expose [Acquia Site Studio](https://www.acquia.com/products-services/acquia-cohesion)'s "In use" stats via drush for easier reporting on Site Studio entities usage.

Site Studio version: >=6.3.0

## Installation

To use this module, you must already have a Drupal site, and Acquia Site Studio.

Add the following to the `repositories` section of your project's composer.json:

```
"blt-site-studio": {
    "type": "vcs",
    "url": "https://github.com/pavlosdan/site-studio-reporting.git"
}
```

or run:

```
composer config repositories.blt-site-studio vcs https://github.com/pavlosdan/site-studio-reporting.git
```

Require the module with Composer:

`composer require acquia/site_studio_reporting`

## Usage examples
```
drush ssr:usage --entity_id ENTITY_ID --entity_type cohesion_component --format table
```
returns usage for entity "ENTITY_ID" of type cohesion_component formatted as a table
```
drush ssr:usage --entity_type cohesion_component --format table
```
returns usage statistics across all Site Studio components installed on the site.


<?php

namespace Drupal\site_studio_reporting\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\cohesion\UsageUpdateManager;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class SiteStudioReportingCommands extends DrushCommands {
  use StringTranslationTrait;

  /**
   * The string translation interface.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface $translationInterface
   */
  protected $stringTranslation;

  /**
   * Holds the usage plugin manager service.
   *
   * @var \Drupal\cohesion\UsageUpdateManager
   */
  protected $usageUpdateManager;

  /**
   * Holds the entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepository
   */
  protected $entityRepository;

  /**
   * Holds the entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   * @param \Drupal\cohesion\UsageUpdateManager $usageUpdateManager
   * @param \Drupal\Core\Database\Connection $connection
   * @param \Drupal\Core\Entity\EntityRepository $entityRepository
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(TranslationInterface $stringTranslation, UsageUpdateManager $usageUpdateManager, EntityRepository $entityRepository, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct();
    $this->stringTranslation = $stringTranslation;
    $this->usageUpdateManager = $usageUpdateManager;
    $this->entityRepository = $entityRepository;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Command description here.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases, config, etc.
   * @option entity_id
   *   Get usage statistics for a particular entity.
   * @option entity_type
   *   Get usage statistics for all entities of this type.
   * @option show_in_use_only
   *   Only show the in use entities.
   * @field-labels
   *   label: Label
   *   entity_id: Entity ID
   *   in_use: In Use
   * @default-fields label,entity_id,in_use
   * @usage site_studio_reporting-usage
   *   Get usage reporting for Site Studio entities.
   *
   * @command site_studio_reporting:usage
   * @aliases ssr:usage
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   */
  public function report($options = ['entity_id' => NULL, 'entity_type' => NULL, 'show_in_use_only' => FALSE, 'format' => NULL]) {
    // Get options.
    $entity_id = (!empty($options['entity_id'])) ? [$options['entity_id']] : NULL;
    $entity_type = $options['entity_type'];
    $show_in_use_only = $options['show_in_use_only'];

    if ($entity_id && !$entity_type) {
      return $this->say(t('You must specify an entity_type using the --entity_type option.'));
    }

    if ($entity_id || $entity_type) {
      $entities = $this->entityTypeManager->getStorage($entity_type)->loadMultiple($entity_id);

      $rows = [];
      foreach($entities as $entity) {
        $entity_in_use = $this->usageUpdateManager->hasInUse($entity);

        if ($show_in_use_only && !$entity_in_use) {
          continue;
        }

        $rows[$entity->get('id')] = [
          'label' => $entity->get('label'),
          'entity_id' => $entity->get('id'),
          'in_use' => $entity_in_use,
        ];
      }

      usort($rows, function ($a, $b) {
          return $a['in_use'] < $b['in_use'];
      });

      return new RowsOfFields($rows);
    }
    // None of the options set.
    else {
      return $this->say(t('You must specify at least the entity type using the --entity_type option.'));
    }
  }

  /**
   * An example of the table output format.
   *
   * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   *
   * @field-labels
   *   group: Group
   *   token: Token
   *   name: Name
   * @default-fields group,token,name
   *
   * @command site_studio_reporting:token
   * @aliases token
   *
   * @filter-default-field name
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   */
  public function token($options = ['format' => 'table']) {
    $all = \Drupal::token()->getInfo();
    foreach ($all['tokens'] as $group => $tokens) {
      foreach ($tokens as $key => $token) {
        $rows[] = [
          'group' => $group,
          'token' => $key,
          'name' => $token['name'],
        ];
      }
    }
    return new RowsOfFields($rows);
  }
}

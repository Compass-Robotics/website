<?php

namespace Drupal\robotics\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;

/**
 * Plugin implementation of the 'competition_leadership' field type.
 *
 * @FieldType(
 *   id = "competition_leadership",
 *   label = @Translation("Competition + leadership role"),
 *   description = @Translation("Stores a competition term reference and a leadership role reference."),
 *   default_widget = "competition_leadership_default",
 *   default_formatter = "competition_leadership_default"
 * )
 */
class CompetitionLeadershipItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['competition_target_id'] = DataReferenceTargetDefinition::create('integer')
      ->setLabel(t('Competition term ID'))
      ->setRequired(FALSE);

    $properties['target_id'] = DataReferenceTargetDefinition::create('integer')
      ->setLabel(t('Leadership role term ID'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'competition_target_id' => [
          'type' => 'int',
          'size' => 'normal',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
        'target_id' => [
          'type' => 'int',
          'size' => 'normal',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
      ],
      'indexes' => [
        'target_id' => ['target_id'],
        'competition_target_id' => ['competition_target_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $competition_target_id = $this->get('competition_target_id')->getValue();
    $target_id = $this->get('target_id')->getValue();

    return ($competition_target_id === NULL || $competition_target_id === '' || (int) $competition_target_id === 0)
      && ($target_id === NULL || $target_id === '' || (int) $target_id === 0);
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'competition_target_id';
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['competition_target_id'] = 0;
    $values['target_id'] = 0;
    return $values;
  }

}

<?php

namespace Drupal\competition_leadership_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;

/**
 * Plugin implementation of the 'competition_leadership' field type.
 *
 * @FieldType(
 *   id = "competition_leadership",
 *   label = @Translation("Competition year + leadership role"),
 *   description = @Translation("Stores a 4-digit competition year and a leadership role reference."),
 *   default_widget = "competition_leadership_default",
 *   default_formatter = "competition_leadership_default"
 * )
 */
class CompetitionLeadershipItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['competition_year'] = DataDefinition::create('integer')
      ->setLabel(t('Competition year'))
      ->setRequired(FALSE)
      ->addConstraint('Range', [
        'min' => 1000,
        'max' => 9999,
      ]);

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
        'competition_year' => [
          'type' => 'int',
          'size' => 'normal',
          'unsigned' => TRUE,
          'not null' => FALSE,
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
        'competition_year' => ['competition_year'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $year = $this->get('competition_year')->getValue();
    $target_id = $this->get('target_id')->getValue();

    return ($year === NULL || $year === '') && ($target_id === NULL || $target_id === '' || (int) $target_id === 0);
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'competition_year';
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['competition_year'] = (int) date('Y');
    $values['target_id'] = 0;
    return $values;
  }

}

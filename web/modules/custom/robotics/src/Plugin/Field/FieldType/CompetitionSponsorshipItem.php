<?php

namespace Drupal\robotics\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;

/**
 * Plugin implementation of the 'competition_sponsorship' field type.
 *
 * @FieldType(
 *   id = "competition_sponsorship",
 *   label = @Translation("Competition year + sponsorship level"),
 *   description = @Translation("Stores a 4-digit competition year and a sponsorship level term reference."),
 *   default_widget = "competition_sponsorship_default",
 *   default_formatter = "competition_sponsorship_default"
 * )
 */
class CompetitionSponsorshipItem extends FieldItemBase {

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
      ->setLabel(t('Sponsorship level term ID'))
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
    return [
      'competition_year' => (int) date('Y'),
      'target_id' => 0,
    ];
  }

}

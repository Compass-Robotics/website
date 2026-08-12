<?php

namespace Drupal\robotics\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Plugin implementation of the 'competition_sponsorship_default' widget.
 *
 * @FieldWidget(
 *   id = "competition_sponsorship_default",
 *   label = @Translation("Competition year + sponsorship level"),
 *   field_types = {
 *     "competition_sponsorship"
 *   }
 * )
 */
class CompetitionSponsorshipDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'sponsorship_vocabulary' => 'sponsorship_levels',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['sponsorship_vocabulary'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sponsorship level vocabulary machine name'),
      '#default_value' => $this->getSetting('sponsorship_vocabulary'),
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      $this->t('Vocabulary: @vocab', ['@vocab' => $this->getSetting('sponsorship_vocabulary')]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta] ?? NULL;
    $vocabulary = $this->getSetting('sponsorship_vocabulary');

    $element['competition_year'] = [
      '#type' => 'number',
      '#title' => $this->t('Competition year'),
      '#default_value' => $item?->competition_year,
      '#min' => 1000,
      '#max' => 9999,
      '#step' => 1,
      '#required' => $this->fieldDefinition->isRequired(),
    ];

    $element['target_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Sponsorship level'),
      '#options' => $this->getSponsorshipOptions($vocabulary),
      '#default_value' => !empty($item?->target_id) ? (int) $item->target_id : '',
      '#empty_option' => $this->t('- Select -'),
      '#empty_value' => '',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      $value['competition_year'] = ($value['competition_year'] === '' || $value['competition_year'] === NULL)
        ? NULL
        : (int) $value['competition_year'];

      $value['target_id'] = ($value['target_id'] === '' || $value['target_id'] === NULL || (int) $value['target_id'] === 0)
        ? 0
        : (int) $value['target_id'];
    }

    return $values;
  }

  /**
   * Build select options for sponsorship levels.
   */
  protected function getSponsorshipOptions(string $vocabulary): array {
    $options = [];

    $term_ids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vocabulary)
      ->sort('weight')
      ->sort('name')
      ->accessCheck(FALSE)
      ->execute();

    if (!$term_ids) {
      return $options;
    }

    $terms = Term::loadMultiple($term_ids);
    foreach ($term_ids as $term_id) {
      if (!isset($terms[$term_id])) {
        continue;
      }

      $options[(int) $term_id] = $terms[$term_id]->label();
    }

    return $options;
  }

}

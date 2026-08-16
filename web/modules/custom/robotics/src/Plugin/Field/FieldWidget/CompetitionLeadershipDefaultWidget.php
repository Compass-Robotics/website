<?php

namespace Drupal\robotics\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Plugin implementation of the 'competition_leadership_default' widget.
 *
 * @FieldWidget(
 *   id = "competition_leadership_default",
 *   label = @Translation("Competition + leadership role"),
 *   field_types = {
 *     "competition_leadership"
 *   }
 * )
 */
class CompetitionLeadershipDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'competition_vocabulary' => 'competitions',
      'leadership_vocabulary' => 'leadership',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['competition_vocabulary'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Competition vocabulary machine name'),
      '#default_value' => $this->getSetting('competition_vocabulary'),
      '#required' => TRUE,
    ];

    $elements['leadership_vocabulary'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Leadership vocabulary machine name'),
      '#default_value' => $this->getSetting('leadership_vocabulary'),
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      $this->t('Competition vocabulary: @vocab', ['@vocab' => $this->getSetting('competition_vocabulary')]),
      $this->t('Vocabulary: @vocab', ['@vocab' => $this->getSetting('leadership_vocabulary')]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta] ?? NULL;
    $competition_vocabulary = $this->getSetting('competition_vocabulary');
    $vocabulary = $this->getSetting('leadership_vocabulary');

    $element['competition_target_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Competition'),
      '#options' => $this->getCompetitionOptions($competition_vocabulary),
      '#default_value' => !empty($item?->competition_target_id) ? (int) $item->competition_target_id : '',
      '#empty_option' => $this->t('- Select -'),
      '#empty_value' => '',
    ];

    $element['target_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Leadership role'),
      '#options' => $this->getLeadershipOptions($vocabulary),
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
      $value['competition_target_id'] = ($value['competition_target_id'] === '' || $value['competition_target_id'] === NULL || (int) $value['competition_target_id'] === 0)
        ? 0
        : (int) $value['competition_target_id'];

      $value['target_id'] = ($value['target_id'] === '' || $value['target_id'] === NULL || (int) $value['target_id'] === 0)
        ? 0
        : (int) $value['target_id'];
    }

    return $values;
  }

  /**
   * Build select options for competitions.
   */
  protected function getCompetitionOptions(string $vocabulary): array {
    $options = [];

    $term_ids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vocabulary)
      ->sort('name', 'DESC')
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

  /**
   * Build select options for leadership roles.
   */
  protected function getLeadershipOptions(string $vocabulary): array {
    $options = [];

    $term_ids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vocabulary)
      ->sort('weight')
      ->sort('name')
      ->accessCheck(TRUE)
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

<?php

namespace Drupal\robotics\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Plugin implementation of the 'competition_leadership_default' formatter.
 *
 * @FieldFormatter(
 *   id = "competition_leadership_default",
 *   label = @Translation("Competition + leadership role"),
 *   field_types = {
 *     "competition_leadership"
 *   }
 * )
 */
class CompetitionLeadershipDefaultFormatter extends FormatterBase {

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
  public function settingsForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
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
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $competition_target_id = (int) ($item->competition_target_id ?? 0);
      $target_id = (int) ($item->target_id ?? 0);
      $competition_label = $competition_target_id > 0
        ? $this->resolveTermLabel($competition_target_id, 'N/A')
        : 'N/A';
      $role_label = $target_id > 0
        ? $this->resolveTermLabel($target_id, 'N/A')
        : 'N/A';

      $elements[$delta] = [
        '#type' => 'item',
        '#markup' => "$competition_label: $role_label",
      ];
    }

    return $elements;
  }

  /**
   * Resolve taxonomy term label by id.
   */
  protected function resolveTermLabel(int $term_id, string $fallback): string {
    $term = Term::load($term_id);
    return $term ? $term->label() : $fallback;
  }

}

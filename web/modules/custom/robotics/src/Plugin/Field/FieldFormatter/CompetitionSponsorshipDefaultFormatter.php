<?php

namespace Drupal\robotics\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Plugin implementation of the 'competition_sponsorship_default' formatter.
 *
 * @FieldFormatter(
 *   id = "competition_sponsorship_default",
 *   label = @Translation("Competition + sponsorship level"),
 *   field_types = {
 *     "competition_sponsorship"
 *   }
 * )
 */
class CompetitionSponsorshipDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'competition_vocabulary' => 'competitions',
      'sponsorship_vocabulary' => 'sponsorship_levels',
      'default_level_label' => 'N/A',
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

    $elements['sponsorship_vocabulary'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sponsorship level vocabulary machine name'),
      '#default_value' => $this->getSetting('sponsorship_vocabulary'),
      '#required' => TRUE,
    ];

    $elements['default_level_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default sponsorship level label'),
      '#default_value' => $this->getSetting('default_level_label'),
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
      $this->t('Vocabulary: @vocab', ['@vocab' => $this->getSetting('sponsorship_vocabulary')]),
      $this->t('Fallback label: @label', ['@label' => $this->getSetting('default_level_label')]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $default_label = $this->getSetting('default_level_label');
    $vocabulary = $this->getSetting('sponsorship_vocabulary');

    foreach ($items as $delta => $item) {
      $competition_target_id = (int) ($item->competition_target_id ?? 0);
      $target_id = (int) ($item->target_id ?? 0);
      $competition_label = $competition_target_id > 0
        ? $this->resolveTermLabel($competition_target_id, 'N/A')
        : 'N/A';

      if ($target_id > 0) {
        $term = Term::load($target_id);
        $level_label = $term ? $term->label() : $default_label;
      }
      else {
        $level_label = $this->resolveDefaultLevelLabel($vocabulary, $default_label);
      }

      $parts = [];
      $parts[] = $this->t('Competition: @competition', ['@competition' => $competition_label]);
      $parts[] = $this->t('Sponsorship level: @level', ['@level' => $level_label]);

      $elements[$delta] = [
        '#type' => 'item',
        '#markup' => implode('<br>', $parts),
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

  /**
   * Resolve fallback label from taxonomy term when possible.
   */
  protected function resolveDefaultLevelLabel(string $vocabulary, string $default_label): string {
    $term_ids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vocabulary)
      ->condition('name', $default_label)
      ->range(0, 1)
      ->accessCheck(TRUE)
      ->execute();

    if (empty($term_ids)) {
      return $default_label;
    }

    $term = Term::load((int) reset($term_ids));
    return $term ? $term->label() : $default_label;
  }

}

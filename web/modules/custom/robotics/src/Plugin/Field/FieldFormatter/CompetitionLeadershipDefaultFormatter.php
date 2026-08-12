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
 *   label = @Translation("Competition year + leadership role"),
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
      'leadership_vocabulary' => 'leadership',
      'member_term_name' => 'Member',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['leadership_vocabulary'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Leadership vocabulary machine name'),
      '#default_value' => $this->getSetting('leadership_vocabulary'),
      '#required' => TRUE,
    ];

    $elements['member_term_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default member term name'),
      '#default_value' => $this->getSetting('member_term_name'),
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      $this->t('Vocabulary: @vocab', ['@vocab' => $this->getSetting('leadership_vocabulary')]),
      $this->t('Default member term name: @label', ['@label' => $this->getSetting('member_term_name')]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $member_label = $this->getSetting('member_term_name');
    $vocabulary = $this->getSetting('leadership_vocabulary');

    foreach ($items as $delta => $item) {
      $year = $item->competition_year;
      $target_id = (int) ($item->target_id ?? 0);

      if ($target_id > 0) {
        $term = Term::load($target_id);
        $role_label = $term ? $term->label() : $member_label;
      }
      else {
        $role_label = $this->resolveMemberLabel($vocabulary, $member_label);
      }

      $parts = [];
      if (!empty($year)) {
        $parts[] = $this->t('Competition year: @year', ['@year' => $year]);
      }
      $parts[] = $this->t('Leadership role: @role', ['@role' => $role_label]);

      $elements[$delta] = [
        '#type' => 'item',
        '#markup' => implode('<br>', $parts),
      ];
    }

    return $elements;
  }

  /**
   * Resolve fallback label from taxonomy term when possible.
   */
  protected function resolveMemberLabel(string $vocabulary, string $member_term_name): string {
    $term_ids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vocabulary)
      ->condition('name', $member_term_name)
      ->range(0, 1)
      ->accessCheck(TRUE)
      ->execute();

    if (empty($term_ids)) {
      return $member_term_name;
    }

    $term = Term::load((int) reset($term_ids));
    return $term ? $term->label() : $member_term_name;
  }

}

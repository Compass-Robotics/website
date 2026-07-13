<?php

namespace Drupal\competition_leadership_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Plugin implementation of the 'competition_leadership_default' widget.
 *
 * @FieldWidget(
 *   id = "competition_leadership_default",
 *   label = @Translation("Competition year + leadership role"),
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
      'leadership_vocabulary' => 'leadership',
      'member_term_name' => 'Member',
      'auto_create_member_term' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
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

    $elements['auto_create_member_term'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto-create default member term if missing'),
      '#default_value' => $this->getSetting('auto_create_member_term'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      $this->t('Vocabulary: @vocab', ['@vocab' => $this->getSetting('leadership_vocabulary')]),
      $this->t('Default role term name: @label', ['@label' => $this->getSetting('member_term_name')]),
      $this->t('Auto-create member term: @value', ['@value' => $this->getSetting('auto_create_member_term') ? 'Yes' : 'No']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta] ?? NULL;
    $vocabulary = $this->getSetting('leadership_vocabulary');
    $member_term_name = $this->getSetting('member_term_name');
    $member_term_id = $this->resolveMemberTermId($vocabulary, $member_term_name, (bool) $this->getSetting('auto_create_member_term'));

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
      '#title' => $this->t('Leadership role'),
      '#options' => $this->getLeadershipOptions($vocabulary, $member_term_name, $member_term_id),
      '#default_value' => !empty($item?->target_id) ? (int) $item->target_id : $member_term_id,
      '#description' => $this->t('When no role is selected, this stores @member as a real term reference.', ['@member' => $member_term_name]),
      '#empty_option' => $this->t('@member (default)', ['@member' => $member_term_name]),
      '#empty_value' => '',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $vocabulary = $this->getSetting('leadership_vocabulary');
    $member_term_name = $this->getSetting('member_term_name');
    $member_term_id = $this->resolveMemberTermId($vocabulary, $member_term_name, (bool) $this->getSetting('auto_create_member_term'));

    foreach ($values as &$value) {
      $value['competition_year'] = ($value['competition_year'] === '' || $value['competition_year'] === NULL)
        ? NULL
        : (int) $value['competition_year'];

      $value['target_id'] = ($value['target_id'] === '' || $value['target_id'] === NULL || (int) $value['target_id'] === 0)
        ? $member_term_id
        : (int) $value['target_id'];
    }

    return $values;
  }

  /**
   * Build select options for leadership roles.
   */
  protected function getLeadershipOptions(string $vocabulary, string $member_term_name, int $member_term_id): array {
    $options = [];

    if ($member_term_id > 0) {
      $options[$member_term_id] = $member_term_name;
    }

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
    foreach ($terms as $term) {
      $options[(int) $term->id()] = $term->label();
    }

    return $options;
  }

  /**
   * Resolve the default member term id in the specified vocabulary.
   */
  protected function resolveMemberTermId(string $vocabulary, string $member_term_name, bool $auto_create = TRUE): int {
    static $resolved = [];
    $cache_key = implode(':', [$vocabulary, $member_term_name, (int) $auto_create]);
    if (isset($resolved[$cache_key])) {
      return $resolved[$cache_key];
    }

    $term_ids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vocabulary)
      ->condition('name', $member_term_name)
      ->range(0, 1)
      ->accessCheck(TRUE)
      ->execute();

    if (!empty($term_ids)) {
      $resolved[$cache_key] = (int) reset($term_ids);
      return $resolved[$cache_key];
    }

    if (!$auto_create || !Vocabulary::load($vocabulary)) {
      $resolved[$cache_key] = 0;
      return 0;
    }

    $term = Term::create([
      'vid' => $vocabulary,
      'name' => $member_term_name,
    ]);
    $term->save();

    $resolved[$cache_key] = (int) $term->id();
    return $resolved[$cache_key];
  }

}

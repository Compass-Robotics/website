<?php

namespace Drupal\robotics\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
class CompetitionLeadershipDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  public function __construct(
    string $plugin_id,
    mixed $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    string $label,
    string $view_mode,
    array $third_party_settings,
    protected readonly RequestStack $requestStack,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
    );
  }

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
    $active_id = (int) ($this->requestStack->getCurrentRequest()->query->get('competition') ?? 0);
    if ($active_id === 0) {
      $active_id = $this->getLatestCompetitionId();
    }

    foreach ($items as $delta => $item) {
      $competition_target_id = (int) ($item->competition_target_id ?? 0);
      $target_id = (int) ($item->target_id ?? 0);
      $competition_label = $competition_target_id > 0
        ? $this->resolveTermLabel($competition_target_id, 'N/A') : 'N/A';
      $role_label = $target_id > 0
        ? $this->resolveTermLabel($target_id, 'N/A') : 'N/A';

      $text = "$competition_label: $role_label";
      $is_active = $active_id > 0 && $competition_target_id === $active_id;

      $elements[$delta] = [
        '#type' => 'item',
        '#markup' => $is_active
          ? '<span class="competition-role--active">' . $text . '</span>'
          : $text,
      ];
    }

    return $elements;
  }

  /** Returns the most recently created competition term ID. */
  protected function getLatestCompetitionId(): int {
    $tids = $this->entityTypeManager->getStorage('taxonomy_term')
      ->getQuery()
      ->condition('vid', $this->getSetting('competition_vocabulary'))
      ->sort('name', 'DESC')
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();
    return $tids ? (int) reset($tids) : 0;
  }

  /**
   * Resolve taxonomy term label by id.
   */
  protected function resolveTermLabel(int $term_id, string $fallback): string {
    $term = Term::load($term_id);
    return $term ? $term->label() : $fallback;
  }

}

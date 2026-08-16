<?php

declare(strict_types=1);

namespace Drupal\robotics\Hook;

use Drupal\Core\Template\Attribute;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views\ViewExecutable;

/**
 * Hook implementations for Views behavior in Robotics.
 */
final class RoboticsViewsHooks {

  /**
   * Exposes custom competition reference subfields for Views.
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data): void {
    $this->addCompetitionReferenceViewsDataForPossibleKeys(
      $data,
      'crm_contact__field_roles',
      ['field_roles_competition_target_id', 'competition_target_id'],
      (string) t('Roles: Competition'),
      (string) t('The competition term referenced by the Roles field item.')
    );

    $this->addCompetitionReferenceViewsDataForPossibleKeys(
      $data,
      'crm_contact__field_team_roles',
      ['field_team_roles_competition_target_id', 'competition_target_id'],
      (string) t('Team roles: Competition'),
      (string) t('The competition term referenced by the Team roles field item.')
    );
  }

  /**
   * Appends a summary row with the total amount to the target table view.
   */
  #[Hook('preprocess_views_view_table')]
  public function preprocessViewsViewTable(array &$variables): void {
    if (empty($variables['view']) || !($variables['view'] instanceof ViewExecutable)) {
      return;
    }

    $view = $variables['view'];
    if ($view->storage->id() !== 'expense_tracker_admin' || $view->current_display !== 'incode_expense_transactions') {
      return;
    }

    if (!isset($view->field['amount'], $view->style_plugin, $variables['header']) || !is_array($variables['header'])) {
      return;
    }

    $total = 0.0;
    $fee_applicable_income_total = 0.0;
    foreach (array_keys($variables['result'] ?? []) as $index) {
      $transaction_type = (string) $view->style_plugin->getFieldValue($index, 'transaction_type');
      $amount_value = $view->style_plugin->getFieldValue($index, 'amount');
      if (!is_numeric($amount_value)) {
        continue;
      }

      $amount = (float) $amount_value;
      $normalized_type = strtolower(trim($transaction_type));

      if ($normalized_type === 'income') {
        $total += $amount;

        $fee_applies_value = $view->style_plugin->getFieldValue($index, 'field_fee_applies');
        if ($this->isTruthyFeeApplies($fee_applies_value)) {
          $fee_applicable_income_total += $amount;
        }
      }
      elseif ($normalized_type === 'expense') {
        $total -= $amount;
      }
    }
    // Our partner's fee is 8% of income.
    $fee_amount = $fee_applicable_income_total * 0.08;

    $column_keys = array_keys($variables['header']);
    if (empty($column_keys)) {
      return;
    }

    $amount_column = in_array('amount', $column_keys, TRUE) ? 'amount' : end($column_keys);
    $fee_column = in_array('field_fee_applies', $column_keys, TRUE) ? 'field_fee_applies' : NULL;
    $title_column = in_array('title', $column_keys, TRUE) ? 'title' : reset($column_keys);

    $summary_row = [
      'columns' => [],
      'attributes' => new Attribute(['class' => ['robotics-total-row']]),
    ];

    foreach ($column_keys as $column_key) {
      $cell_markup = '';
      if ($column_key === $title_column) {
        $cell_markup = (string) t('Total');
      }
      if ($column_key === $amount_column) {
        $cell_markup = '$' . number_format($total, 2, '.', ',');
      }
      if ($fee_column !== NULL && $column_key === $fee_column) {
        $cell_markup = (string) t('8% fee = @fee', ['@fee' => '$' . number_format($fee_amount, 2, '.', ',')]);
      }

      $summary_row['columns'][$column_key] = [
        'default_classes' => TRUE,
        'attributes' => new Attribute(),
        'fields' => [],
        'content' => $cell_markup === '' ? [] : [
          [
            'field_output' => ['#markup' => $cell_markup],
          ],
        ],
      ];
    }

    $variables['rows'][] = $summary_row;
  }

  /**
   * Determines whether a fee-applies field value should be treated as true.
   */
  private function isTruthyFeeApplies(mixed $value): bool {
    if (is_bool($value)) {
      return $value;
    }

    $normalized = strtolower(trim((string) $value));
    return in_array($normalized, ['1', 'true', 'yes', 'on'], TRUE);
  }

  /**
   * Adds Views metadata for a competition subfield column.
   */
  private function addCompetitionReferenceViewsData(
    array &$data,
    string $table,
    string $column,
    string $title,
    string $help
  ): void {
    if (empty($data[$table]) || !array_key_exists($column, $data[$table])) {
      return;
    }

    $data[$table][$column]['title'] = $title;
    $data[$table][$column]['help'] = $help;
    $data[$table][$column]['field'] = [
      'id' => 'numeric',
    ];
    $data[$table][$column]['filter'] = [
      'id' => 'taxonomy_index_tid',
      'vocabulary' => 'competitions',
    ];
    $data[$table][$column]['argument'] = [
      'id' => 'taxonomy_index_tid',
    ];
    $data[$table][$column]['sort'] = [
      'id' => 'standard',
    ];
    $data[$table][$column]['relationship'] = [
      'id' => 'standard',
      'base' => 'taxonomy_term_field_data',
      'base field' => 'tid',
      'relationship field' => $column,
      'label' => (string) t('Competition term'),
    ];
  }

  /**
   * Adds Views metadata for the first matching competition subfield key.
   */
  private function addCompetitionReferenceViewsDataForPossibleKeys(
    array &$data,
    string $table,
    array $possible_columns,
    string $title,
    string $help
  ): void {
    foreach ($possible_columns as $column) {
      if (!empty($data[$table]) && array_key_exists($column, $data[$table])) {
        $this->addCompetitionReferenceViewsData($data, $table, $column, $title, $help);
      }
    }
  }

}

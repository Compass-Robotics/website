<?php

declare(strict_types=1);

namespace Drupal\robotics\Hook;

use Drupal\Core\Template\Attribute;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views\Plugin\views\query\Sql;
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
   * Applies default competition filter value for the Members view.
   */
  #[Hook('views_pre_view')]
  public function viewsPreView(ViewExecutable $view, string $display_id, array &$args): void {
    if (!$this->isMembersView($view, $display_id)) {
      return;
    }

    $selected_competition_id = $this->extractSelectedCompetitionId($view);
    $reset_requested = $this->isResetRequested();
    if ($selected_competition_id > 0 && !$reset_requested) {
      return;
    }

    $default_competition_id = $this->getTopCompetitionTermId();
    if ($default_competition_id <= 0) {
      return;
    }

    $exposed_input = $view->getExposedInput();
    $exposed_input['competition'] = (string) $default_competition_id;
    $view->setExposedInput($exposed_input);
  }

  /**
   * Alters Members view sorting based on selected competition role weight.
   */
  #[Hook('views_query_alter')]
  public function viewsQueryAlter(ViewExecutable $view, Sql $query): void {
    if (!$this->isMembersView($view, $view->current_display)) {
      return;
    }

    $competition_id = $this->extractSelectedCompetitionId($view);
    if ($competition_id <= 0) {
      return;
    }
    $competition_id = (int) $competition_id;
    $roles_competition_column = $this->getRolesCompetitionColumn();
    $roles_leadership_column = $this->getRolesLeadershipColumn();

    $base_alias = $query->ensureTable($view->storage->get('base_table'));

    $role_weight_subquery = "
      SELECT MIN(leadership_term.weight)
      FROM {crm_contact__field_roles} roles_sort
      INNER JOIN {taxonomy_term_field_data} leadership_term
        ON leadership_term.tid = roles_sort.{$roles_leadership_column}
      WHERE roles_sort.entity_id = {$base_alias}.id
        AND roles_sort.deleted = 0
                AND roles_sort.{$roles_competition_column} = {$competition_id}
        AND roles_sort.{$roles_leadership_column} > 0
        AND leadership_term.vid = 'leadership'
    ";
    $role_rank_formula = "COALESCE(($role_weight_subquery), 2147483647)";

    $query->addOrderBy(
      NULL,
      $role_rank_formula,
      'ASC',
      'members_role_rank_sort'
    );
    $query->addOrderBy(
      NULL,
      "
        (
          SELECT MIN(sort_user.name)
          FROM {crm_contact__field_user} sort_user_ref
          LEFT JOIN {users_field_data} sort_user
            ON sort_user.uid = sort_user_ref.field_user_target_id
          WHERE sort_user_ref.entity_id = {$base_alias}.id
            AND sort_user_ref.deleted = 0
        )
      ",
      'ASC',
      'members_username_sort'
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
   * Checks whether the current view display is Members.
   */
  private function isMembersView(ViewExecutable $view, string $display_id): bool {
    return $view->storage->id() === 'members' && in_array($display_id, ['default', 'members'], TRUE);
  }

  /**
   * Gets the selected competition term id from exposed input.
   */
  private function extractSelectedCompetitionId(ViewExecutable $view): int {
    $exposed_input = $view->getExposedInput();
    if (!is_array($exposed_input)) {
      return 0;
    }

    $raw_value = $exposed_input['competition'] ?? NULL;
    if (is_array($raw_value)) {
      $raw_value = reset($raw_value);
    }

    if ($raw_value === NULL || $raw_value === '' || strtolower((string) $raw_value) === 'all') {
      return 0;
    }

    return (int) $raw_value;
  }

  /**
   * Resolves the top competitions term by vocabulary sort order.
   */
  private function getTopCompetitionTermId(): int {
    $term_ids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'competitions')
      ->sort('weight', 'ASC')
      ->sort('name', 'ASC')
      ->accessCheck(TRUE)
      ->range(0, 1)
      ->execute();

    if (empty($term_ids)) {
      return 0;
    }

    return (int) reset($term_ids);
  }

  /**
   * Detects whether an exposed form reset was requested.
   */
  private function isResetRequested(): bool {
    $request = \Drupal::request();
    $reset_value = strtolower(trim((string) $request->query->get('reset', '')));
    if (in_array($reset_value, ['1', 'true', 'yes', 'on'], TRUE)) {
      return TRUE;
    }

    $op_value = strtolower(trim((string) $request->query->get('op', '')));
    return $op_value === 'reset';
  }

  /**
   * Resolves the actual competition subfield column used in field_roles data.
   */
  private function getRolesCompetitionColumn(): string {
    $schema = \Drupal::database()->schema();
    if ($schema->fieldExists('crm_contact__field_roles', 'field_roles_competition_target_id')) {
      return 'field_roles_competition_target_id';
    }

    return 'competition_target_id';
  }

  /**
   * Resolves the actual leadership-role subfield column in field_roles data.
   */
  private function getRolesLeadershipColumn(): string {
    $schema = \Drupal::database()->schema();
    if ($schema->fieldExists('crm_contact__field_roles', 'field_roles_target_id')) {
      return 'field_roles_target_id';
    }

    return 'target_id';
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

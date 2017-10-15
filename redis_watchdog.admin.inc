<?php


/**
 * Validate result from redis_watchdog administration filter form.
 */
function redis_watchdog_filter_form_validate($form, &$form_state) {
  if ($form_state['values']['op'] == t('Filter') && empty($form_state['values']['type'])) {
    form_set_error('type', t('To filter, you must make a selection.'));
  }
}

/**
 * Process result from redis_watchdog administration filter form.
 *
 * @todo form filter not working.
 */
function redis_watchdog_filter_form_submit($form, &$form_state) {
  $op = $form_state['values']['op'];
  switch ($op) {
    case t('Filter'):
      // If the type is not empty set the page to the type form.
      if (isset($form_state['values']['type']) && !empty($form_state['values']['type'])) {
        $_SESSION['redis_watchdog_overview_filter']['type'] = $form_state['values']['type'];
      }
      break;
    case t('Reset'):
      $_SESSION['redis_watchdog_overview_filter'] = [];
      break;
  }
  return 'admin/reports/redis_watchdog';
}

<?php

/**
 * Returns HTML for a log message.
 *
 * @param $variables
 *   An associative array containing:
 *   - event: An object with at least the message and variables properties.
 *   - link: (optional) Format message as link, event->wid is required.
 *
 * @return array
 *   Array of themeable items.
 *
 * @ingroup themeable
 */
function theme_redis_watchdog_message($variables) {
  $output = '';
  $event = $variables['event'];
  // Check for required properties.
  if (isset($event->message) && isset($event->variables)) {
    // Messages without variables or user specified text.
    if ($event->variables === 'N;') {
      $output = $event->message;
    }
    // Message to translate with injected variables.
    else {
      $output = t($event->message, unserialize($event->variables));
    }
    if ($variables['link'] && isset($event->wid)) {
      // Truncate message to 56 chars.
      $output = truncate_utf8(filter_xss($output, []), 56, TRUE, TRUE);
      $output = l($output, 'admin/reports/redislog/event/' . $event->wid, ['html' => TRUE]);
    }
  }
  return $output;
}

/**
 * Return form for redis_watchdog administration filters.
 *
 * @ingroup forms
 * @see redis_watchdog_filter_form_submit()
 * @see redis_watchdog_filter_form_validate()
 * @see redis_watchdog_overview()
 */
function redis_watchdog_filter_form($form) {
  // Message types.
  $wd_types = _redis_watchdog_get_message_types();

  // Build a selection list of log types.
  $form['filters'] = [
    '#type' => 'fieldset',
    '#title' => t('Filter log messages by type'),
    '#collapsible' => empty($_SESSION['redis_watchdog_overview_filter']),
    '#collapsed' => TRUE,
  ];
  $form['filters']['type'] = [
    '#title' => t('Available types'),
    '#type' => 'select',
    '#multiple' => TRUE,
    '#size' => 8,
    '#options' => array_flip($wd_types),
  ];
  $form['filters']['actions'] = [
    '#type' => 'actions',
    '#attributes' => ['class' => ['container-inline']],
  ];
  $form['filters']['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => t('Filter'),
  ];

  if (!empty($_SESSION['redis_watchdog_overview_filter'])) {
    $form['filters']['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => t('Reset'),
    ];
  }

  if (!empty($_SESSION['redis_watchdog_overview_filter']['type'])) {
    $form['filters']['type']['#default_value'] = $_SESSION['redis_watchdog_overview_filter']['type'];
  }
  return $form;
}

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

/**
 * This returns a themeable form that displays the total log count for different
 * types of logs.
 *
 * @return array
 */
function redis_watchdog_log_type_count_table() {
  // Get the counts.
  $wd_types_count = _redis_watchdog_get_message_types_count();
  $header = [
    t('Log Type'),
    t('Count'),
  ];
  $rows = [];
  foreach ($wd_types_count as $key => $value) {
    $rows[] = [
      'data' => [
        // Cells
        $key,
        $value,
      ],
    ];
  }
  // Table of log items.
  $build['redis_watchdog_type_count_table'] = [
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => ['id' => 'admin-redis_watchdog_type_count'],
    '#empty' => t('No log messages available.'),
  ];

  return $build;
}

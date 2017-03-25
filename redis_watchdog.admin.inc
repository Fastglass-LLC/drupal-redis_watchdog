<?php

/**
 * Menu callback; displays a listing of log messages.
 *
 * Messages are truncated at 56 chars. Full-length message could be viewed at
 * the message details page.
 */
function redis_watchdog_overview() {
  $filter = redis_watchdog_build_filter_query();
  $rows = [];
  $classes = [
    WATCHDOG_DEBUG => 'redis_watchdog-debug',
    WATCHDOG_INFO => 'redis_watchdog-info',
    WATCHDOG_NOTICE => 'redis_watchdog-notice',
    WATCHDOG_WARNING => 'redis_watchdog-warning',
    WATCHDOG_ERROR => 'redis_watchdog-error',
    WATCHDOG_CRITICAL => 'redis_watchdog-critical',
    WATCHDOG_ALERT => 'redis_watchdog-alert',
    WATCHDOG_EMERGENCY => 'redis_watchdog-emerg',
  ];

  $header = [
    '', // Icon column.
    ['data' => t('Type'), 'field' => 'w.type'],
    ['data' => t('Date'), 'field' => 'w.wid', 'sort' => 'desc'],
    t('Message'),
    ['data' => t('User'), 'field' => 'u.name'],
    ['data' => t('Operations')],
  ];
  $log = redis_watchdog_client();
  $result = $log->getMultiple(50, 'wid', 'DESC');
  foreach ($result as $log) {
    $rows[] = [
      'data' =>
        [
          // Cells
          ['class' => 'icon'],
          t($log->type),
          format_date($log->timestamp, 'short'),
          theme('redis_watchdog_message', ['event' => $log, 'link' => TRUE]),
          theme('username', ['account' => $log]),
          filter_xss($log->link),
        ],
      // Attributes for tr
      'class' => [
        drupal_html_class('dblog-' . $log->type),
        $classes[$log->severity],
      ],
    ];
  }

  // Filter form.
  $build['redis_watchdog_filter_form'] = drupal_get_form('redis_watchdog_filter_form');
  // Clear log form.
  $build['redis_watchdog_clear_log_form'] = drupal_get_form('redis_watchdog_clear_log_form');

  // Table of log items.
  $build['redis_watchdog_table'] = [
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => ['id' => 'admin-redis_watchdog'],
    '#empty' => t('No log messages available.'),
  ];
  $build['redis_watchdog_pager'] = ['#theme' => 'pager'];

  return $build;
}

/**
 * Menu callback; displays details about a log message.
 */
function redis_watchdog_event($id) {
  $severity = watchdog_severity_levels();
  $log = redis_watchdog_client();
  $result = $log->getSingle($id);
  if ($log = $result) {
    $rows = [
      [
        ['data' => t('Type'), 'header' => TRUE],
        t($log->type),
      ],
      [
        ['data' => t('Date'), 'header' => TRUE],
        format_date($log->timestamp, 'long'),
      ],
      [
        ['data' => t('User'), 'header' => TRUE],
        theme('username', ['account' => $log]),
      ],
      [
        ['data' => t('Location'), 'header' => TRUE],
        l($log->location, $log->location),
      ],
      [
        ['data' => t('Referrer'), 'header' => TRUE],
        l($log->referer, $log->referer),
      ],
      [
        ['data' => t('Message'), 'header' => TRUE],
        theme('redis_watchdog_message', ['event' => $log]),
      ],
      [
        ['data' => t('Severity'), 'header' => TRUE],
        $severity[$log->severity],
      ],
      [
        ['data' => t('Hostname'), 'header' => TRUE],
        check_plain($log->hostname),
      ],
      [
        ['data' => t('Operations'), 'header' => TRUE],
        $log->link,
      ],
    ];
    $build['redis_watchdog_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#attributes' => ['class' => ['redis_watchdog-event']],
    ];
    return $build;
  }
  else {
    return '';
  }
}

/**
 * Build query for redis_watchdog administration filters based on session.
 */
function redis_watchdog_build_filter_query() {
  if (empty($_SESSION['redis_watchdog_overview_filter'])) {
    return;
  }

  $filters = redis_watchdog_filters();

  // Build query
  $where = $args = [];
  foreach ($_SESSION['redis_watchdog_overview_filter'] as $key => $filter) {
    $filter_where = [];
    foreach ($filter as $value) {
      $filter_where[] = $filters[$key]['where'];
      $args[] = $value;
    }
    if (!empty($filter_where)) {
      $where[] = '(' . implode(' OR ', $filter_where) . ')';
    }
  }
  $where = !empty($where) ? implode(' AND ', $where) : '';

  return [
    'where' => $where,
    'args' => $args,
  ];
}


/**
 * List redis_watchdog administration filters that can be applied.
 */
function redis_watchdog_filters() {
  $filters = [];

  $wd_types = _redis_watchdog_get_message_types();
  if (!empty($wd_types)) {
    foreach ($wd_types as $type) {
      $types[$type] = t($type);
    }

    if (!empty($types)) {
      $filters['type'] = [
        'title' => t('Type'),
        'where' => "w.type = ?",
        'options' => $types,
      ];
    }
  }

  $filters['severity'] = [
    'title' => t('Severity'),
    'where' => 'w.severity = ?',
    'options' => watchdog_severity_levels(),
  ];

  return $filters;
}

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
 */
function redis_watchdog_filter_form($form) {
  $filters = redis_watchdog_filters();

  $form['filters'] = [
    '#type' => 'fieldset',
    '#title' => t('Filter log messages'),
    '#collapsible' => TRUE,
    '#collapsed' => empty($_SESSION['redis_watchdog_overview_filter']),
  ];
  foreach ($filters as $key => $filter) {
    $form['filters']['status'][$key] = [
      '#title' => $filter['title'],
      '#type' => 'select',
      '#multiple' => TRUE,
      '#size' => 8,
      '#options' => $filter['options'],
    ];
    if (!empty($_SESSION['redis_watchdog_overview_filter'][$key])) {
      $form['filters']['status'][$key]['#default_value'] = $_SESSION['redis_watchdog_overview_filter'][$key];
    }
  }

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

  return $form;
}

/**
 * Validate result from redis_watchdog administration filter form.
 */
function redis_watchdog_filter_form_validate($form, &$form_state) {
  if ($form_state['values']['op'] == t('Filter') && empty($form_state['values']['type']) && empty($form_state['values']['severity'])) {
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
  $filters = redis_watchdog_filters();
  switch ($op) {
    case t('Filter'):
      foreach ($filters as $name => $filter) {
        if (isset($form_state['values'][$name])) {
          $_SESSION['redis_watchdog_overview_filter'][$name] = $form_state['values'][$name];
        }
      }
      break;
    case t('Reset'):
      $_SESSION['redis_watchdog_overview_filter'] = [];
      break;
  }
  return 'admin/reports/redis_watchdog';
}

/**
 * Return form for redis_watchdog clear button.
 *
 * @ingroup forms
 * @see redis_watchdog_clear_log_submit()
 */
function redis_watchdog_clear_log_form($form) {
  $form['redis_watchdog_clear'] = [
    '#type' => 'fieldset',
    '#title' => t('Clear log messages'),
    '#description' => t('This will permanently remove the log messages from the database.'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['redis_watchdog_clear']['clear'] = [
    '#type' => 'submit',
    '#value' => t('Clear log messages'),
    '#submit' => ['redis_watchdog_clear_log_submit'],
  ];

  return $form;
}

/**
 * Submit callback: clear database with log messages.
 */
function redis_watchdog_clear_log_submit() {
  $_SESSION['redis_watchdog_overview_filter'] = [];
  $log = redis_watchdog_client();
  $log->clear();

  drupal_set_message(t('Database log cleared.'));
}


<?php

/**
 * Menu callback; displays a listing of log messages.
 *
 * Messages are truncated at 56 chars. Full-length message could be viewed at
 * the message details page.
 */
function redis_watchdog_overview() {
  $filter = redis_watchdog_build_filter_query();
  $rows = array();
  $classes = array(
    WATCHDOG_DEBUG     => 'redis_watchdog-debug',
    WATCHDOG_INFO      => 'redis_watchdog-info',
    WATCHDOG_NOTICE    => 'redis_watchdog-notice',
    WATCHDOG_WARNING   => 'redis_watchdog-warning',
    WATCHDOG_ERROR     => 'redis_watchdog-error',
    WATCHDOG_CRITICAL  => 'redis_watchdog-critical',
    WATCHDOG_ALERT     => 'redis_watchdog-alert',
    WATCHDOG_EMERGENCY => 'redis_watchdog-emerg',
  );

  $header = array(
    '', // Icon column.
    array('data' => t('Type'), 'field' => 'w.type'),
    array('data' => t('Date'), 'field' => 'w.wid', 'sort' => 'desc'), t('Message'),
    array('data' => t('User'), 'field' => 'u.name'),
    array('data' => t('Operations')),
  );

  $log = new RedisLog();
  $result = $log->getMultiple(50, 'wid', 'DESC');
  foreach ($result as $log) {
    $rows[] = array('data' =>
      array(
        // Cells
        array('class' => 'icon'),
        t($log->type),
        format_date($log->timestamp, 'short'),
        theme('redis_watchdog_message', array('event' => $log, 'link' => TRUE)),
        theme('username', array('account' => $log)),
        filter_xss($log->link),
      ),
      // Attributes for tr
      'class' => array(drupal_html_class('dblog-' . $log->type), $classes[$log->severity]),
    );
  }

  $build['redis_watchdog_filter_form'] = drupal_get_form('redis_watchdog_filter_form');
  $build['redis_watchdog_clear_log_form'] = drupal_get_form('redis_watchdog_clear_log_form');

  $build['redis_watchdog_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => array('id' => 'admin-redis_watchdog'),
    '#empty' => t('No log messages available.'),
  );
  $build['redis_watchdog_pager'] = array('#theme' => 'pager');

  return $build;
}

/**
 * Menu callback; displays details about a log message.
 */
function redis_watchdog_event($id) {
  $severity = watchdog_severity_levels();
  $log = new RedisLog();
  $result = $log->get($id);
  if ($log = $result) {
    $rows = array(
      array(
        array('data' => t('Type'), 'header' => TRUE),
        t($log->type),
      ),
      array(
        array('data' => t('Date'), 'header' => TRUE),
        format_date($log->timestamp, 'long'),
      ),
      array(
        array('data' => t('User'), 'header' => TRUE),
        theme('username', array('account' => $log)),
      ),
      array(
        array('data' => t('Location'), 'header' => TRUE),
        l($log->location, $log->location),
      ),
      array(
        array('data' => t('Referrer'), 'header' => TRUE),
        l($log->referer, $log->referer),
      ),
      array(
        array('data' => t('Message'), 'header' => TRUE),
        theme('redis_watchdog_message', array('event' => $log)),
      ),
      array(
        array('data' => t('Severity'), 'header' => TRUE),
        $severity[$log->severity],
      ),
      array(
        array('data' => t('Hostname'), 'header' => TRUE),
        check_plain($log->hostname),
      ),
      array(
        array('data' => t('Operations'), 'header' => TRUE),
        $log->link,
      ),
    );
    $build['redis_watchdog_table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#attributes' => array('class' => array('redis_watchdog-event')),
    );
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
  $where = $args = array();
  foreach ($_SESSION['redis_watchdog_overview_filter'] as $key => $filter) {
    $filter_where = array();
    foreach ($filter as $value) {
      $filter_where[] = $filters[$key]['where'];
      $args[] = $value;
    }
    if (!empty($filter_where)) {
      $where[] = '(' . implode(' OR ', $filter_where) . ')';
    }
  }
  $where = !empty($where) ? implode(' AND ', $where) : '';

  return array(
    'where' => $where,
    'args' => $args,
  );
}


/**
 * List redis_watchdog administration filters that can be applied.
 */
function redis_watchdog_filters() {
  $filters = array();

  $wd_types = _redis_watchdog_get_message_types();
  if (!empty($wd_types)) {
    foreach ($wd_types as $type) {
      $types[$type] = t($type);
    }

    if (!empty($types)) {
      $filters['type'] = array(
        'title' => t('Type'),
        'where' => "w.type = ?",
        'options' => $types,
      );
    }
  }

  $filters['severity'] = array(
    'title' => t('Severity'),
    'where' => 'w.severity = ?',
    'options' => watchdog_severity_levels(),
  );

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
      $output = truncate_utf8(filter_xss($output, array()), 56, TRUE, TRUE);
      $output = l($output, 'admin/reports/redislog/event/' . $event->wid, array('html' => TRUE));
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

  $form['filters'] = array(
    '#type' => 'fieldset',
    '#title' => t('Filter log messages'),
    '#collapsible' => TRUE,
    '#collapsed' => empty($_SESSION['redis_watchdog_overview_filter']),
  );
  foreach ($filters as $key => $filter) {
    $form['filters']['status'][$key] = array(
      '#title' => $filter['title'],
      '#type' => 'select',
      '#multiple' => TRUE,
      '#size' => 8,
      '#options' => $filter['options'],
    );
    if (!empty($_SESSION['redis_watchdog_overview_filter'][$key])) {
      $form['filters']['status'][$key]['#default_value'] = $_SESSION['redis_watchdog_overview_filter'][$key];
    }
  }

  $form['filters']['actions'] = array(
    '#type' => 'actions',
    '#attributes' => array('class' => array('container-inline')),
  );
  $form['filters']['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Filter'),
  );
  if (!empty($_SESSION['redis_watchdog_overview_filter'])) {
    $form['filters']['actions']['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset')
    );
  }

  return $form;
}

/**
 * Validate result from redis_watchdog administration filter form.
 */
function redis_watchdog_filter_form_validate($form, &$form_state) {
  if ($form_state['values']['op'] == t('Filter') && empty($form_state['values']['type']) && empty($form_state['values']['severity'])) {
    form_set_error('type', t('You must select something to filter by.'));
  }
}

/**
 * Process result from redis_watchdog administration filter form.
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
      $_SESSION['redis_watchdog_overview_filter'] = array();
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
  $form['redis_watchdog_clear'] = array(
    '#type' => 'fieldset',
    '#title' => t('Clear log messages'),
    '#description' => t('This will permanently remove the log messages from the database.'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['redis_watchdog_clear']['clear'] = array(
    '#type' => 'submit',
    '#value' => t('Clear log messages'),
    '#submit' => array('redis_watchdog_clear_log_submit'),
  );

  return $form;
}

/**
 * Submit callback: clear database with log messages.
 */
function redis_watchdog_clear_log_submit() {
  $_SESSION['redis_watchdog_overview_filter'] = array();

  $log = new RedisLog();
  $log->clear();

  drupal_set_message(t('Database log cleared.'));
}

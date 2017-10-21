<?php
/**
 * Created by PhpStorm.
 * User: bowens
 * Date: 10/6/17
 * Time: 21:49
 */

namespace Drupal\redis_watchdog\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\redis_watchdog\RedisWatchdog;

class RedisWatchdogOverviewFilter extends FormBase {

  const SESSION_KEY = 'redis_watchdog_overview_filter';

  protected $redis;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redis_watchdog_overview_filter';
  }

  /**
   * @inheritDoc
   */
  public function __construct() {
    $this->redis = new RedisWatchdog();
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Message types.
    // @todo remove this once working

    $wd_types = $this->redis->get_message_types();
    $session_filter = $_SESSION[static::SESSION_KEY] ?? [];

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

    if (!empty($session_filter)) {
      $form['filters']['actions']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => [],
        '#submit' => ['::resetForm'],
      ];
    }

    if (!empty($session_filter)) {
      $form['filters']['type']['#default_value'] = $session_filter;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $filters = $this->redis->get_message_types();
    foreach ($filters as $name => $filter) {
      if ($form_state->hasValue($filter)) {
        $_SESSION[static::SESSION_KEY][$name] = $form_state->getValue($filter);
      }
    }
  }

  /**
   * Custom reset function for the form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */

  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION[static::SESSION_KEY] = [];
  }


}
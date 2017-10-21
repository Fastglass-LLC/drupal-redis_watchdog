<?php

namespace Drupal\redis_watchdog\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the SimpleForm form controller.
 *
 * This example demonstrates a simple form with a singe text input element. We
 * extend FormBase which is the simplest form base class used in Drupal.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class ExportForm extends FormBase {

  /**
   * Export form.
   *
   * @param array $form
   *   Default form array structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object containing current form state.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['redis_watchdog_export'] = [
      '#markup' => '<p>' . t('Click the link below to export all of the logs in Redis to a CSV file.') . '</p>',
      '#weight' => -1,
    ];
    // @todo This might not be needed
    $form['downloadbutton'] = [
      '#type' => 'submit',
      '#value' => t('Download log messages'),
    ];

    // return $form;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redis_watchdog_export';
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo does this work
    // $form_state['redirect'] = 'admin/reports/redislog/export/download';
    $form_state->setRedirect('redis_watchdog.export-download');
  }

}
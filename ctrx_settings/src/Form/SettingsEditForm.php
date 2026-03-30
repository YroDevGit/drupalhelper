<?php

namespace Drupal\ctrx_settings\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

class SettingsEditForm extends FormBase {

  public function getFormId() {
    return 'ctrx_settings_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {

    $record = Database::getConnection()
      ->select('ctrx_settings', 'c')
      ->fields('c')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    $form['id'] = [
      '#type' => 'hidden',
      '#value' => $record->id,
    ];

    $form['setting_key'] = [
      '#type' => 'textfield',
      '#title' => 'Key',
      '#default_value' => $record->setting_key,
      '#disabled' => TRUE, // optional (recommended)
    ];

    $form['setting_value'] = [
      '#type' => 'textfield',
      '#title' => 'Value',
      '#default_value' => $record->setting_value,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Update',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $id = $form_state->getValue('id');
    $value = $form_state->getValue('setting_value');

    Database::getConnection()
      ->update('ctrx_settings')
      ->fields(['setting_value' => $value])
      ->condition('id', $id)
      ->execute();

    $this->messenger()->addStatus('Updated successfully.');
  }
}

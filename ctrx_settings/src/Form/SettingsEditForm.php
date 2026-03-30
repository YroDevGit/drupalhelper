<?php

namespace Drupal\ctrx_settings\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

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

    if (!$record) {
      $this->messenger()->addError('Setting not found.');
      return $form;
    }

    // =========================
    // BACK BUTTON
    // =========================
    $form['actions']['back'] = [
      '#type' => 'link',
      '#title' => '← Back to list',
      '#url' => Url::fromRoute('ctrx_settings.page'),
    ];

    // =========================
    // ID (hidden)
    // =========================
    $form['id'] = [
      '#type' => 'hidden',
      '#value' => $record->id,
    ];

    // =========================
    // KEY (readonly)
    // =========================
    $form['setting_key'] = [
      '#type' => 'textfield',
      '#title' => 'Key',
      '#default_value' => $record->setting_key,
      '#disabled' => TRUE,
    ];

    // =========================
    // TYPE (readonly)
    // =========================
    $type = $record->setting_type ?? 'text';

    $form['setting_type'] = [
      '#type' => 'textfield',
      '#title' => 'Type',
      '#default_value' => $type,
      '#disabled' => TRUE,
    ];

    // =========================
    // VALUE (DYNAMIC BASED ON TYPE)
    // =========================

    $value = $record->setting_value;

    switch ($type) {

      case 'textarea':
        $form['setting_value'] = [
          '#type' => 'textarea',
          '#title' => 'Value',
          '#default_value' => $value,
        ];
        break;

      case 'number':
        $form['setting_value'] = [
          '#type' => 'number',
          '#title' => 'Value',
          '#default_value' => $value,
        ];
        break;

      case 'image':
        $form['setting_value'] = [
          '#type' => 'entity_autocomplete',
          '#title' => 'Select Image (Media)',
          '#target_type' => 'media',
          '#selection_settings' => [
            'target_bundles' => ['image'], // only image media
          ],
          '#tags' => FALSE, // single selection only
          '#description' => 'Search by media name, value will be stored as MID',
          '#default_value' => $value ? \Drupal\media\Entity\Media::load($value) : NULL
        ];
        break;

      case 'array':
        $decoded = json_decode($value, true);

        $form['setting_value'] = [
          '#type' => 'textarea',
          '#title' => 'JSON Value',
          '#default_value' => json_encode($decoded, JSON_PRETTY_PRINT),
          '#description' => 'Edit JSON carefully',
        ];
        break;

      default:
        $form['setting_value'] = [
          '#type' => 'textfield',
          '#title' => 'Value',
          '#default_value' => $value,
        ];
        break;
    }

    // =========================
    // SUBMIT
    // =========================

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Update',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $id = $form_state->getValue('id');
    $value = $form_state->getValue('setting_value');

    // get type from DB (safe)
    $record = Database::getConnection()
      ->select('ctrx_settings', 'c')
      ->fields('c', ['setting_type'])
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    $type = $record->setting_type ?? 'text';

    // =========================
    // VALIDATION
    // =========================

    if ($type === 'number' && !is_numeric($value)) {
      $form_state->setErrorByName('setting_value', 'Value must be a number.');
      return;
    }

    if ($type === 'array') {
      json_decode($value);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName('setting_value', 'Invalid JSON format.');
        return;
      }
    }

    if ($type === 'image') {
      if (!is_numeric($value)) {
        $form_state->setErrorByName('setting_value', 'Image must be File ID (FID).');
        return;
      }
    }

    // =========================
    // UPDATE DB
    // =========================

    Database::getConnection()
      ->update('ctrx_settings')
      ->fields([
        'setting_value' => $value,
      ])
      ->condition('id', $id)
      ->execute();

    $this->messenger()->addStatus('Updated successfully.');

    $form_state->setRedirect('ctrx_settings.page');
  }
}

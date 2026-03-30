<?php

namespace Drupal\ctrx_settings\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ctrx_settings\SettingsStorage;

class SettingsPage extends FormBase
{

  public function getFormId()
  {
    return 'ctrx_settings_page';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {

    $items = SettingsStorage::all();

    // =========================
    // ADD NEW SETTING
    // =========================

    $form['new_key'] = [
      '#type' => 'textfield',
      '#title' => 'Key',
      '#required' => TRUE,
    ];

    $form['new_type'] = [
      '#type' => 'select',
      '#title' => 'Value Type',
      '#options' => [
        'text' => 'Text',
        'textarea' => 'Textarea',
        'number' => 'Number',
        'image' => 'Image (FID)',
        'array' => 'Array (JSON)',
        'any' => 'Any',
      ],
      '#default_value' => $form_state->getValue('new_type') ?? 'text',

      // 🔥 AJAX ADDED
      '#ajax' => [
        'callback' => '::updateValueField',
        'wrapper' => 'value-field-wrapper',
        'event' => 'change',
      ],
    ];

    // =========================
    // DYNAMIC VALUE FIELD (WRAPPER)
    // =========================

    $form['value_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'value-field-wrapper',
      ],
    ];

    $type = $form_state->getValue('new_type') ?? 'text';

    $form['value_wrapper']['new_value'] = $this->buildValueField($type);

    $form['add'] = [
      '#type' => 'submit',
      '#value' => 'Save Key/Value',
      '#submit' => ['::addItem'],
    ];

    // =========================
    // TABLE LIST
    // =========================

    $header = ['Key', 'Type', 'Value', 'Actions'];
    $rows = [];

    foreach ($items as $item) {

      $edit_url = Url::fromRoute('ctrx_settings.edit', [
        'id' => $item->id,
      ])->toString();

      $delete_url = Url::fromRoute('ctrx_settings.delete', [
        'id' => $item->id,
      ])->toString();

      // format value display
      $value = $item->setting_value;
      $item->setting_type = $item->setting_type ?? "text";

      if ($item->setting_type === 'array') {
        $decoded = json_decode($value, true);
        $value = '<pre>' . print_r($decoded, true) . '</pre>';
      }

      if ($item->setting_type === 'image') {
        $value = 'FID: ' . $value;
      }

      $rows[] = [
        'key' => $item->setting_key,
        'type' => $item->setting_type ?? 'text',
        'value' => [
          'data' => [
            '#markup' => $value,
          ],
        ],
        'actions' => [
          'data' => [
            '#markup' => "
              <a href='{$edit_url}'>Edit</a> |
              <a href='{$delete_url}'>Delete</a>
            ",
          ],
        ],
      ];
    }

    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => 'No settings found.',
    ];

    return $form;
  }

  // =========================
  // AJAX CALLBACK
  // =========================

  public function updateValueField(array &$form, FormStateInterface $form_state)
  {
    return $form['value_wrapper'];
  }

  // =========================
  // FIELD BUILDER
  // =========================

  private function buildValueField($type)
  {

    switch ($type) {

      case 'textarea':
        return [
          '#type' => 'textarea',
          '#title' => 'Value',
        ];

      case 'number':
        return [
          '#type' => 'number',
          '#title' => 'Value',
        ];

      case 'image':
        return [
          '#type' => 'entity_autocomplete',
          '#title' => 'Select Image (Media)',
          '#target_type' => 'media',
          '#selection_settings' => [
            'target_bundles' => ['image'], // only image media
          ],
          '#tags' => FALSE, // single selection only
          '#description' => 'Search by media name, value will be stored as MID',
        ];

      case 'array':
        return [
          '#type' => 'textarea',
          '#title' => 'JSON Value',
        ];

      default:
        return [
          '#type' => 'textfield',
          '#title' => 'Value',
        ];
    }
  }

  // =========================
  // SAVE NEW ITEM
  // =========================

  public function addItem(array &$form, FormStateInterface $form_state)
  {

    $key = $form_state->getValue('new_key');
    $value = $form_state->getValue('new_value');
    $type = $form_state->getValue('new_type');

    if (!$key) {
      return;
    }

    // handle type conversion
    if ($type === 'number') {
      if (!is_numeric($value)) {
        $form_state->setErrorByName('new_value', 'Value must be a number');
        return;
      }
    }

    if ($type === 'array') {
      json_decode($value);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName('new_value', 'Invalid JSON format');
        return;
      }
    }

    if ($type === 'image') {
      // expected FID (for now)
      if (!is_numeric($value)) {
        $form_state->setErrorByName('new_value', 'Image must be File ID (FID)');
        return;
      }
    }

    SettingsStorage::set($key, $value, $type);

    $this->messenger()->addStatus('Setting saved successfully.');
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {}
}

<?php

namespace Drupal\ctrx_settings\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ctrx_settings\SettingsStorage;

class SettingsPage extends FormBase {

  public function getFormId() {
    return 'ctrx_settings_page';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $items = SettingsStorage::all();

    // ADD NEW FIELD
    $form['new_key'] = [
      '#type' => 'textfield',
      '#title' => 'Key',
    ];

    $form['new_value'] = [
      '#type' => 'textfield',
      '#title' => 'Value',
    ];

    $form['add'] = [
      '#type' => 'submit',
      '#value' => 'Save Key/Value',
      '#submit' => ['::addItem'],
    ];

    // TABLE HEADER
    $header = ['Key', 'Value', 'Actions'];
    $rows = [];

    foreach ($items as $item) {

      $edit_url = \Drupal\Core\Url::fromRoute('ctrx_settings.edit', [
        'id' => $item->id,
      ])->toString();

      $delete_url = \Drupal\Core\Url::fromRoute('ctrx_settings.delete', [
        'id' => $item->id,
      ])->toString();

      $rows[] = [
        'key' => $item->setting_key,
        'value' => $item->setting_value,
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

  public function addItem(array &$form, FormStateInterface $form_state) {
    $key = $form_state->getValue('new_key');
    $value = $form_state->getValue('new_value');

    if ($key) {
      SettingsStorage::set($key, $value);
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {}
}

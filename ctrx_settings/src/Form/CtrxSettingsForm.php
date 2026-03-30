<?php

namespace Drupal\ctrx_settings\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class CtrxSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['ctrx.settings'];
  }

  public function getFormId() {
    return 'ctrx_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('ctrx.settings');

    $form['projname'] = [
      '#type' => 'textfield',
      '#title' => 'Project Name',
      '#default_value' => $config->get('projname'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('ctrx.settings')
      ->set('projname', $form_state->getValue('projname'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}

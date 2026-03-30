<?php

namespace Drupal\ctrx_settings\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;

class SettingsDeleteForm extends ConfirmFormBase {

  protected $id;

  public function getFormId() {
    return 'ctrx_settings_delete_form';
  }

  public function getQuestion() {
    return 'Are you sure you want to delete this setting?';
  }

  public function getCancelUrl() {
    return new Url('ctrx_settings.page');
  }

  public function getConfirmText() {
    return 'Delete';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $this->id = $id;
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    Database::getConnection()
      ->delete('ctrx_settings')
      ->condition('id', $this->id)
      ->execute();

    $this->messenger()->addStatus('Deleted successfully.');
    $form_state->setRedirect('ctrx_settings.page');
  }
}

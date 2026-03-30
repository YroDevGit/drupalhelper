<?php

namespace Drupal\ctrx_settings;

use Drupal\Core\Database\Database;

class SettingsStorage {

  public static function all() {
    return Database::getConnection()
      ->select('ctrx_settings', 'c')
      ->fields('c')
      ->execute()
      ->fetchAll() ?? [];
  }

  public static function get($key) {
    return Database::getConnection()
      ->select('ctrx_settings', 'c')
      ->fields('c', ['setting_value'])
      ->condition('setting_key', $key)
      ->execute()
      ->fetchField() ?? NULL;
  }

  public static function set($key, $value) {
    $db = Database::getConnection();

    $exists = $db->select('ctrx_settings', 'c')
      ->fields('c', ['id'])
      ->condition('setting_key', $key)
      ->execute()
      ->fetchField();

    if ($exists) {
      $db->update('ctrx_settings')
        ->fields(['setting_value' => $value])
        ->condition('setting_key', $key)
        ->execute();
    }
    else {
      $db->insert('ctrx_settings')
        ->fields([
          'setting_key' => $key,
          'setting_value' => $value,
        ])
        ->execute();
    }
  }

  public static function delete($key) {
    Database::getConnection()
      ->delete('ctrx_settings')
      ->condition('setting_key', $key)
      ->execute();
  }
}

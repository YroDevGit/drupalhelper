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

  public static function set($key, $value, $type) {
    $db = Database::getConnection();

    $exists = $db->select('ctrx_settings', 'c')
      ->fields('c', ['id'])
      ->condition('setting_key', $key)
      ->execute()
      ->fetchField();

    if ($exists) {
      $db->update('ctrx_settings')
        ->fields(['setting_value' => $value, "setting_type" => $type])
        ->condition('setting_key', $key)
        ->execute();
    }
    else {
      $db->insert('ctrx_settings')
        ->fields([
          'setting_key' => $key,
          'setting_value' => $value,
          "setting_type" => $type
        ])
        ->execute();
    }
  }

  public static function getMedia($key, $field = 'field_media_image') {

    $mid = self::get($key);
    if (!$mid) return NULL;

    $media = Media::load($mid);
    if (!$media) return NULL;

    if (!$media->hasField($field)) return NULL;

    $file = $media->get($field)->entity;
    if (!$file) return NULL;

    return file_create_url($file->getFileUri());
  }

  public static function delete($key) {
    Database::getConnection()
      ->delete('ctrx_settings')
      ->condition('setting_key', $key)
      ->execute();
  }
}

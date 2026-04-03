<?php

namespace Ctrx;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

class DrupalHelper
{
  public static function getTaxonomy(
    string $vocabulary,
    array $includes = ['id', 'name']
  ): array {

    $tree = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree($vocabulary);

    // Collect IDs
    $tids = array_column($tree, 'tid');

    // Load full entities
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadMultiple($tids);

    $result = [];

    foreach ($terms as $term) {

      $row = [];

      foreach ($includes as $field) {
        $row[$field] = self::resolve($term, $field);
      }

      $result[] = $row;
    }
    return $result;
  }

  private static function resolve($item, string $field)
  {
    if ($field === 'id') {
      return $item->id();
    }

    if ($field === 'name') {
      return $item->getName();
    }

    if ($field === 'description') {
      return $item->getDescription();
    }

    // ✅ Handle custom fields properly
    if ($item->hasField($field) && !$item->get($field)->isEmpty()) {
      return $item->get($field)->value;
    }

    return null;
  }

  public static function blockSubmiteFilterCTR($data, $except, $form, $form_state, &$conf = [])
  {
    foreach ($data as $k => $v) {
      if (in_array($v, $except)) continue;
      if (isset($v['type'])) {
        if ($v['type'] == "submit") continue;
        if ($v['type'] == "markup" || $v['type'] == "html") continue;
        if ($v['type'] == "fieldset") {
          $items = $v['items'];
          if (! empty($items)) {
            foreach ($items as $ko => $lo) {
              self::blockSubmiteFilterCTR($lo, [], $form, $form_state, $conf[$k]);
            }
          }
        }
        $val = $form_state->getValue($k) ?? NULL;
        if ($v['type'] == "file") {
          if (isset($v['picker']) && $v['picker'] == "media") {
            \Ctrx\DrupalMedia::getMediaDetail($val, $conf[$k]);
            continue;
          }
        }
        if ($v['type'] == "auto" || $v['type'] == "auto_complete") {
          $node = \Drupal\node\Entity\Node::load($val);
          if ($node) {
            $url = $node->toUrl()->toString();
            $val = [
              "page_id" => $val,
              "url" => $url
            ];
          }
        }
        $conf[$k] = $val;
      }
    }
  }

  /**
   * $key should be type: fieldset
   */
  public static function addOne(string $key, array $values, FormStateInterface &$form_state)
  {
    $itemKey = $key;
    $current = $form_state->get($itemKey);
    if (self::arrayHasKeys($values)) {
      $current[] = $values;
    } else {
      $pw = [];
      foreach ($values as $k => $v) {
        $pw[$v] = "-";
      }
      $current[] = $pw;
    }

    $form_state->set($itemKey, $current);
    $newInput = $form_state->getUserInput();
    $form_state->setUserInput($newInput);

    $form_state->setRebuild(TRUE);
  }

  public static function clearItems(string $key, FormStateInterface &$form_state)
  {
    $form_state->set($key, []);
    $newInput = $form_state->getUserInput();
    $form_state->setUserInput($newInput);

    $form_state->setRebuild(TRUE);
  }


  /**
   * $key should be type: fieldset
   */
  public static function removeOne(string $key, FormStateInterface &$form_state)
  {
    $itemKey = $key;
    $trigger = $form_state->getTriggeringElement();
    $index = $trigger["#index"] ?? 0;
    $items = $form_state->get($itemKey) ?? [];
    unset($items[$index]);
    $newItems = array_values($items);

    $form_state->set($itemKey, $newItems);
    $newInput = $form_state->getUserInput();
    unset($newInput['settings'][$itemKey][$index]);
    $newData = array_values($newInput['settings'][$itemKey] ?? []);
    $newInput['settings'][$itemKey] = $newData;
    $form_state->setUserInput($newInput);
    $form_state->setRebuild(TRUE);
  }

  public static function ajaxCallback(string $key, FormStateInterface &$form_state)
  {
    $itemKey = $key;
    $complete_form = $form_state->getCompleteForm();

    if (isset($complete_form[$itemKey])) {
      return $complete_form[$itemKey];
    }

    if (isset($complete_form['settings'][$itemKey])) {
      return $complete_form['settings'][$itemKey];
    }
    return $complete_form;
  }

  public static function defaultConfig(array $data)
  {
    $ret = [];
    foreach ($data as $k => $v) {
      if (isset($v['type'])) {
        $type = $v['type'];
        if ($type == "fieldset") {
          $ret[$k] = [];
        } else if ($type == "file" || $type == "file_managed") {
          $ret[$k] = [];
        } else if ($type == "submit") {
          continue;
        } else {
          $ret[$k] = $v['default'] ?? "";
        }
      }
    }
    return $ret;
  }

  private static function arrayHasKeys(array $data): bool
  {
    $isAssoc = array_keys($data) !== range(0, count($data) - 1);
    return $isAssoc;
  }
}

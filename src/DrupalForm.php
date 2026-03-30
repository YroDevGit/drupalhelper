<?php

namespace Ctrx;

use Drupal\media\Entity\Media;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

class DrupalForm
{

  public static function filterBlockFormCtr($data, FormStateInterface $form_state, string|int $parent = null, $counter = -1, $config = [], $class = null)
  {
    $form = [];
    foreach ($data as $k => $v) {
      $conf = [];
      if ($parent) {
        //$conf = $this->configuration[$parent] ?? null;
      } else {
        $conf = $config[$k] ?? null;
      }
      if (isset($v['type']) && $v['type'] == "checkbox") {
        $label = \Drupal::translation()->translate($v['label'] ?? ucfirst($k));
        $form[$k] = [
          '#type' => "checkbox",
          '#title' => $label,
          '#default_value' => $conf ?? 0,
        ];
        continue;
      }
      if (isset($v['type']) && $v['type'] == "select") {
        $label = \Drupal::translation()->translate($v['label'] ?? ucfirst($k));
        $name = $v['name'] ?? $label;
        $form[$k] = [
          '#type' => "select",
          '#title' => \Drupal::translation()->translate($v['label'] ?? ucfirst($k)),
          '#options' => $v['options'] ?? ["0" => "Select " . $name],
          '#default_value' => $conf ?? "0",
        ];
        continue;
      }
      if (isset($v['type']) && in_array($v['type'], ['auto', 'auto_complete'])) {

        $target_type = $v['target_type'] ?? 'node';
        $bundles = $v['target'] ?? ["page"];

        $form[$k] = [
          '#type' => 'entity_autocomplete',
          '#title' => $v['label'] ?? ucfirst($k),
          '#target_type' => $target_type,
          '#selection_settings' => [
            'target_bundles' => $bundles,
          ],
          '#tags' => FALSE,
          '#description' => 'Search by entity name',
        ];

        if (!empty($conf)) {
          if ($target_type == 'media') {
            $form[$k]['#default_value'] = \Drupal\media\Entity\Media::load($conf['image_fid'] ?? NULL);
          } elseif ($target_type == 'node') {
            $form[$k]['#default_value'] = \Drupal\node\Entity\Node::load($conf["page_id"] ?? NULL);
          }
        }
        continue;
      }
      if (isset($v['type']) && ($v['type'] == "manage_file" || $v['type'] == "file")) {
        if (isset($v['picker']) && ($v['picker'] == "media" || $v['picker'] == "media_library")) {
          $form[$k] = [
            '#type' => 'media_library',
            '#allowed_bundles' => $v['allowed'] ?? ['image'], // Restrict to image media types
            '#title' => \Drupal::translation()->translate($v['label'] ?? $k),
            '#description' => \Drupal::translation()->translate('Upload a new image or choose an existing one from the library.'),
            '#cardinality' => 1,
          ];
          $conf = $conf ?? [];
          if (isset($conf['image_fid'])) {
            $id = $conf['image_fid'];
            if (is_array($id)) {
              $form[$k]['#default_value'] = $id;
            } else {
              $form[$k]['#default_value'] = [$id];
            }
          }
          continue;
        }
        $form[$k] = [
          '#type' => 'managed_file',
          '#title' => \Drupal::translation()->translate($v['label'] ?? $k),
          '#upload_location' => 'public://hero_banner/',
          '#default_value' => $conf ?? [],
        ];
        if (isset($v['validator'])) {
          $form[$k]["#upload_validators"] = $v['validator'];
          //'file_validate_extensions' => ['png jpg jpeg gif'],
          //'file_validate_image_resolution' => ['50x50', '2000x2000'], // optional
        }
        continue;
      }
      if (isset($v['type']) && $v['type'] == "submit") {
        $form[$k] = [
          '#type' => 'submit',
          '#value' => \Drupal::translation()->translate($v['label'] ?? "Submit"),
        ];

        if (isset($v['id'])) {
          $form[$k]["#" . $v['id']] = $counter;
        }

        if (isset($v['ajax'])) {
          if (is_string($v['ajax']['callback'])) {
            $form[$k]["#ajax"]['callback'] = [get_class($class), $v['ajax']['callback']];
            $form[$k]["#ajax"]['wrapper'] = $v['ajax']['wrapper'];
          } else {
            $form[$k]["#ajax"] = $v['ajax'];
          }
        }

        if (isset($v['action']) || isset($v['callback'])) {
          $form[$k]['#submit'] = [
            [get_class($class), $v['action'] ?? $v['callback']]
          ];
        }
        continue;
      }

      if (isset($v['type']) && $v['type'] == "fieldset") {
        $items = $v['items'] ?? [];

        $triggerBtn = true;
        $akinIto = $form_state->get($k);

        $form_state->set($k, $form_state->get($k) ?? $config[$k] ?? []);

        if ($akinIto === NULL) {
          $triggerBtn = false;
          $akinIto = $form_state->get($k);
        }
        $akinIto = array_values($akinIto);

        $num_items = 0;
        if ($form_state->get($k)) {
          $num_items = count($akinIto);
        } else {
          $num_items = count($akinIto);
        }

        $wrap = $k . "-" . "wrapper";
        if (isset($v['wrapper'])) {
          $wrap =  $v['wrapper'];
        }
        $form[$k] = [
          '#type' => 'fieldset',
          '#title' => \Drupal::translation()->translate($v['label'] ?? ucfirst($k)),
          '#tree' => TRUE,
          '#prefix' => "<div id='$wrap'>",
          '#suffix' => '</div>',
        ];

        $children = self::filterBlockFormCtr($items, $form_state, $k, -1, $config, $class);

        for ($i = 0; $i < $num_items; $i++) {
          $atinIto = $akinIto[$i] ?? [];
          foreach ($children as $ikey => $child) {
            if (! isset($v['autoload']) || $v['autoload'] == false) {
              if (! isset($atinIto[$ikey])) continue;
            }
            $form[$k][$i]['divider'] = [
              '#markup' => '<hr style="margin:10px 0;">',
            ];

            if (isset($child['#type']) && $child['#type'] == 'submit') {
              $child['#limit_validation_errors'] = [];
              $child['#attributes']['data-index'] = $i;
              $child["#name"] = $i + 1;
              $child["#index"] = $i;
              if (isset($items[$ikey]['id'])) {
                $child['#attributes']["#" . $items[$ikey]['id']] = $i;
              }
            } else if (isset($child['#type']) && ($child['#type'] == 'file' || $child['#type'] == 'managed_file' || $child['#type'] == "media_library")) {
              if (isset($atinIto[$ikey]) && ! is_array($atinIto[$ikey])) {
                $atinIto[$ikey] = [];
              }
              $child['#default_value'] =  $atinIto[$ikey] ?? [];
            } else if (isset($child['#type']) && $child['#type'] == "checkbox") {
              $child['#default_value'] = $atinIto[$ikey] ?? 0;
            } else {
              $child['#default_value'] = $atinIto[$ikey] ?? "";
            }
            //dump($child);
            $form[$k][$i][$ikey] = $child;
          }
        }
        continue;
      }

      $value = "";
      $vl = $config[$k] ?? "";
      if (isset($v['trim'])) {
        $trm = $v['trim'];
        if (is_bool($trm)) {
          if ($trm == false) {
            $value = $vl;
          } else {
            if ($trm == true) {
              $value = trim($vl);
            }
          }
        } else if (is_string($trm)) {
          $value = trim($vl, $trm);
        }
      } else {
        $value = trim($vl);
      }

      $form[$k] = [
        '#type' => $v['type'] ?? "textfield",
        '#title' => \Drupal::translation()->translate($v['label'] ?? ucfirst($k)),
        '#default_value' => $value,
      ];
    }
    return $form;
  }
}

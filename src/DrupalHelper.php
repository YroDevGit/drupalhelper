<?php

namespace Ctrx;

class DrupalHelper
{
    public static function getTaxonomy(
        string $vocabulary,
        array $includes = ['id', 'name']
    ): array {

        $items = \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->loadTree($vocabulary);

        $result = [];

        foreach ($items as $item) {

            $row = [];

            foreach ($includes as $field) {
                $row[$field] = self::resolve($item, $field);
            }

            $result[] = $row;
        }

        return $result;
    }

    private static function resolve($item, string $field)
    {
        if ($field === 'id') {
            return $item->tid ?? null;
        }

        if ($field === 'name') {
            return $item->name ?? null;
        }

        if ($field === 'description' && method_exists($item, 'getDescription')) {
            $desc = $item->getDescription();
            return is_object($desc) ? ($desc->value ?? null) : $desc;
        }

        if (isset($item->{$field})) {
            return $item->{$field};
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
        if($v['type'] == "file"){
          if(isset($v['picker']) && $v['picker'] == "media"){
            \Ctrx\DrupalMedia::getMediaDetail($val, $conf[$k]);
            continue;
          }
        }
        if($v['type'] == "auto" || $v['type'] == "auto_complete"){
          $node = \Drupal\node\Entity\Node::load($val);
          if($node){
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
}

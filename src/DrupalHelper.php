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
}
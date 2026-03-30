<?php

namespace Ctrx;

class DrupalAdvanceHelper
{
    public static function taxonomy(
        string $vocabulary,
        array $map = ['id', 'name'],
        bool $asOptions = false
    ): array {

        $items = EntityProvider::loadTaxonomyTree($vocabulary);

        $data = [];

        foreach ($items as $item) {

            $row = EntityTransformer::transform($item, $map);

            if ($asOptions) {
                $key = $row['id'] ?? null;
                $value = $row['name'] ?? null;

                if ($key !== null) {
                    $data[$key] = $value;
                }

            } else {
                $data[] = $row;
            }
        }

        return $data;
    }

    public static function entity(
        string $type,
        array $conditions = [],
        array $map = ['id', 'name']
    ): array {

        $entities = EntityProvider::load($type, $conditions);

        $data = [];

        foreach ($entities as $entity) {
            $data[] = EntityTransformer::transform($entity, $map);
        }

        return $data;
    }
}
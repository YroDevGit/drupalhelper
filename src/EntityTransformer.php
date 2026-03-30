<?php

namespace Ctrx;

class EntityTransformer
{
    public static function transform(object $item, array $map): array
    {
        $result = [];

        foreach ($map as $key => $source) {

            // ['id', 'name']
            if (is_int($key)) {
                $field = $source;
                $result[$field] = self::resolve($item, $field);
                continue;
            }

            // ['label' => 'name']
            $result[$key] = self::resolve($item, $source);
        }

        return $result;
    }

    private static function resolve(object $item, string $field)
    {
        // 1. Direct property (tid, name, etc.)
        if (isset($item->{$field})) {
            return $item->{$field};
        }

        // 2. Taxonomy description support (IMPORTANT FIX)
        if ($field === 'description' && method_exists($item, 'getDescription')) {
            $desc = $item->getDescription();
            return is_object($desc) ? ($desc->value ?? null) : $desc;
        }

        // 3. Drupal entity fields (field API)
        if (method_exists($item, 'get')) {
            $fieldObj = $item->get($field);

            if ($fieldObj && method_exists($fieldObj, 'getValue')) {
                $value = $fieldObj->getValue();
                return $value[0]['value'] ?? null;
            }
        }

        // 4. label fallback
        if ($field === 'name' && method_exists($item, 'label')) {
            return $item->label();
        }

        // 5. id fallback
        if ($field === 'id' && method_exists($item, 'id')) {
            return $item->id();
        }

        return null;
    }
}
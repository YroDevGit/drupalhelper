<?php
namespace Ctrx;

class EntityProvider
{
    public static function load(string $entityType, array $conditions = []): array
    {
        $storage = \Drupal::entityTypeManager()->getStorage($entityType);

        return $storage->loadByProperties($conditions);
    }

    public static function loadTaxonomyTree(string $vocabulary): array
    {
        return \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->loadTree($vocabulary);
    }
}
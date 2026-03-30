<?php

namespace Ctrx;

use Drupal\media\Entity\Media;

class Ctrx
{

    /**
     * Extract media details from media_library or raw input
     */


    public static function getMediaDetail($item)
    {
        if (is_string($item) && $item) {
            $exp = explode(",", $item);
            $exp = array_reverse($exp);
            $id = $exp[0];
            $file = \Drupal\media\Entity\Media::load($id);
            $fl = $file->get('field_media_image')->entity;

            if ($file && $fl) {
                return [
                    'image_fid' => $file->id(),
                    'image_url' => \Drupal::service('file_url_generator')
                        ->generateAbsoluteString($fl->getFileUri()),
                ];
            }
        } else {
            return [];
        }
    }

    public static function getFileDetails(array|null $item)
    {
        if (is_array($item)) {
            if (! empty($item)) {
                $file = \Drupal\file\Entity\File::load($item[0]);
                if ($file) {
                    $file->setPermanent(); 
                    $file->save();
                    return [
                        'image_fid' => $file->id(),
                        'image_url' => \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri())
                    ];
                }
            }
        }
    }

    public static function get($raw)
    {

        $mid = self::extractMediaId($raw);

        if (!$mid) {
            return null;
        }

        $media = Media::load($mid);

        if (!$media) {
            return null;
        }

        // Try to find image/file field dynamically
        foreach ($media->getFields() as $field) {
            $type = $field->getFieldDefinition()->getType();

            if (in_array($type, ['image', 'file'])) {
                $file = $field->entity ?? null;

                if ($file) {
                    return [
                        'mid' => $media->id(),
                        'fid' => $file->id(),
                        'url' => \Drupal::service('file_url_generator')
                            ->generateAbsoluteString($file->getFileUri()),
                    ];
                }
            }
        }

        return [
            'mid' => $media->id(),
            'fid' => null,
            'url' => null,
        ];
    }

    /**
     * Extract media ID from string or array
     */
    private static function extractMediaId($raw)
    {

        if (is_array($raw)) {
            return $raw[0] ?? null;
        }

        if (is_string($raw)) {
            if (preg_match('/^(\d+)/', $raw, $m)) {
                return (int) $m[1];
            }
        }

        if (is_numeric($raw)) {
            return (int) $raw;
        }

        return null;
    }
}

<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Media\Fields;

use Bonfire\Fields\Field;
use Bonfire\Fields\FieldException;
use Bonfire\Fields\FieldType;
use Bonfire\Media\Entities\Media;
use Bonfire\Media\Models\MediaModel;

/**
 * One file from the media library, picked or uploaded in a picker over the Media browser. What is stored is the
 * file's ID, so moving or renaming the file never breaks the reference. `folder` is the folder the picker opens
 * on and uploads to. A file that has been deleted is refused, so the field can't be saved until it is fixed.
 */
class FileField extends FieldType
{
    public function partial(): string
    {
        return 'file';
    }

    public function viewNamespace(): string
    {
        return 'Bonfire\Media\Views\fields';
    }

    public function check(Field $field): void
    {
        $folder = $field->options['folder'] ?? null;

        if ($folder !== null && ! is_string($folder)) {
            throw new FieldException("Field `{$field->handle}`: `folder` is the name of a media folder, such as products.");
        }
    }

    public function prepare(mixed $input, Field $field, array $submitted): string
    {
        return trim(parent::prepare($input, $field, $submitted));
    }

    /**
     * The ID has to belong to a file in the library, not in the Recycler, that the type accepts.
     */
    public function rules(Field $field): array|string
    {
        return $this->withRules($field, 'permit_empty', function (mixed $value, mixed $data, ?string &$error = null): bool {
            $media = is_string($value) ? model(MediaModel::class)->find($value) : null;

            if (! $media instanceof Media) {
                $error = lang('Media.fileNotFound');

                return false;
            }

            if (! $this->accepts($media)) {
                $error = lang('Media.notAnImage');

                return false;
            }

            return true;
        });
    }

    public function toStored(string $input, Field $field): ?string
    {
        return $input === '' ? null : $input;
    }

    /**
     * Whether a file of the library can be picked for this type.
     */
    public function accepts(Media $media): bool
    {
        return true;
    }
}

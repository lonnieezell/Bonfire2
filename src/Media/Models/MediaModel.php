<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Media\Models;

use Bonfire\Core\Like;
use Bonfire\Media\Entities\Media;
use CodeIgniter\Model;

/**
 * One row per media file. Deleting is soft, so the file stays on disk until the Recycler purges it.
 */
class MediaModel extends Model
{
    protected $table            = 'media';
    protected $useAutoIncrement = false;
    protected $returnType       = Media::class;
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'id', 'container', 'folder', 'path', 'filename', 'mime', 'size', 'width', 'height', 'alt',
    ];
    protected $beforeInsert = ['addId'];

    /**
     * Keeps files whose original name or alt text contains the term. A % or _ in it matches itself.
     */
    public function matching(string $term): self
    {
        $needle = Like::escape(trim($term), $this->db);

        return $this->groupStart()
            ->like('filename', $needle, 'both', true, true)
            ->orLike('alt', $needle, 'both', true, true)
            ->groupEnd();
    }

    /**
     * The folders files are listed under, in order, without the root.
     *
     * @return list<string>
     */
    public function folders(): array
    {
        $rows = $this->builder()->distinct()->select('folder')
            ->where('folder !=', '')->where('deleted_at', null)
            ->orderBy('folder')->get()->getResultArray();

        return array_column($rows, 'folder');
    }

    /**
     * Gives a new row a random (version 4) UUID.
     *
     * @param array{data: array<string, mixed>} $event
     *
     * @return array{data: array<string, mixed>}
     */
    protected function addId(array $event): array
    {
        $bytes    = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        $event['data']['id'] ??= vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));

        return $event;
    }
}

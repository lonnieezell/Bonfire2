<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Media\Config;

use CodeIgniter\Config\BaseConfig;

class Media extends BaseConfig
{
    /**
     * Where uploads live, by name: the directory on disk and the URL it is served at, relative to the base URL.
     *
     * @var array<string, array{path: string, url: string}>
     */
    public array $containers = [
        'public' => ['path' => FCPATH . 'uploads/media', 'url' => 'uploads/media'],
    ];

    /**
     * The largest upload accepted, in kilobytes.
     */
    public int $maxSize = 10240;

    /**
     * The file extensions accepted, in lower case. SVG is left out because one served from the site can run script.
     *
     * @var list<string>
     */
    public array $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif',
        'pdf', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'zip', 'mp3', 'mp4', 'webm',
    ];
}

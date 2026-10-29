<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Tools\Libraries;

/**
 * Checks the install's environment, for the System Info page and the
 * System health dashboard widget alike.
 */
class SystemHealth
{
    public function __construct(
        private readonly string $writablePath = WRITEPATH,
        private readonly string $envFile = ROOTPATH . '.env',
    ) {
    }

    /**
     * The writable folder itself, then each file and folder directly in it.
     *
     * @return list<array{name: string, dir: bool, writable: bool, modified?: int|null, size?: int|null}>
     */
    public function files(): array
    {
        helper('filesystem');

        $files = [['name' => '/writable', 'dir' => true, 'writable' => is_really_writable($this->writablePath)]];

        foreach (get_dir_file_info($this->writablePath, true) as $name => $info) {
            $isDir   = is_dir($this->writablePath . $name);
            $files[] = [
                'name'     => trim($name, ' /'),
                'dir'      => $isDir,
                'writable' => is_really_writable($this->writablePath . $name),
                'modified' => $isDir ? null : $info['date'],
                'size'     => $isDir ? null : $info['size'],
            ];
        }

        return $files;
    }

    /**
     * What is wrong with the install, if anything.
     *
     * @return list<array{problem: string, subject: string}>
     */
    public function problems(float $maxLoad): array
    {
        $problems = [];

        foreach ($this->files() as $file) {
            if (! $file['writable']) {
                $problems[] = ['problem' => 'notWritable', 'subject' => $file['name']];
            }
        }

        if (! $this->envPresent()) {
            $problems[] = ['problem' => 'envMissing', 'subject' => '.env'];
        }

        $load = $this->serverLoad();
        if ($load !== null && $load > $maxLoad) {
            $problems[] = ['problem' => 'highLoad', 'subject' => number_format($load, 1)];
        }

        return $problems;
    }

    public function envPresent(): bool
    {
        return is_file($this->envFile);
    }

    /**
     * The 1-minute load average, or null where the platform can't tell.
     */
    public function serverLoad(): ?float
    {
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : false;

        return $load === false ? null : $load[0];
    }
}

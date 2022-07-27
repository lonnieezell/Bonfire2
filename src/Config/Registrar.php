<?php

namespace Bonfire\Config;

use Bonfire\Auth\Filters\Admin;
use Bonfire\Consent\Filters\ConsentFilter;
use Bonfire\Core\Filters\OnlineCheck;
use Bonfire\Users\Validation\UserRules;
use Bonfire\View\Decorator;
use CodeIgniter\Shield\Authentication\Passwords\ValidationRules as PasswordRules;
use CodeIgniter\Shield\Filters\ChainAuth;
use CodeIgniter\Shield\Filters\SessionAuth;
use CodeIgniter\Shield\Filters\TokenAuth;
use ReflectionProperty;

include_once __DIR__ . '/Constants.php';
include_once __DIR__ . '/../Common.php';

class Registrar
{
    private static $nonModuleFolders = [
        'Config', 'Core',
    ];

    public static function Pager(): array
    {
        return [
            'templates' => [
                'bonfire_full'   => 'Bonfire\Views\_pager_full',
                'bonfire_simple' => 'Bonfire\Views\_pager_simple',
                'bonfire_head'   => 'Bonfire\Views\_pager_head',
            ],
        ];
    }

    /**
     * Registers the Shield filters.
     */
    public static function Filters()
    {
        return [
            'aliases' => [
                'session' => SessionAuth::class,
                'tokens'  => TokenAuth::class,
                'chain'   => ChainAuth::class,
                'online'  => OnlineCheck::class,
                'consent' => ConsentFilter::class,
                'admin'   => Admin::class,
            ],
            'globals' => [
                'before' => [
                    'online' => ['except' => 'site-offline'],
                ],
                'after' => [
                    'alerts',
                    'consent' => ['except' => ADMIN_AREA . '*'],
                ],
            ],
            'filters' => [
                'session' => [
                    'before' => [ADMIN_AREA . '*'],
                ],
                'admin' => [
                    'before' => [ADMIN_AREA . '*'],
                ],
            ],
        ];
    }

    public static function Validation()
    {
        return [
            'ruleSets' => [
                PasswordRules::class,
                UserRules::class,
            ],
            'users' => [
                'email'      => 'required|valid_email|unique_email[{id}]',
                'username'   => 'required|string|is_unique[users.username,id,{id}]',
                'first_name' => 'permit_empty|string|min_length[3]',
                'last_name'  => 'permit_empty|string|min_length[3]',
            ],
        ];
    }

    public static function View()
    {
        return [
            'decorators' => [
                Decorator::class,
            ],
        ];
    }

    /**
     * Registers all Bonfire Module namespaces
     */
    public static function registerNamespaces(): void
    {
        helper('filesystem');
        $map = directory_map(__DIR__ . '/../', 1);
        /** @var \CodeIgniter\Autoloader\Autoloader $autoloader */
        $autoloader = service('autoloader');

        $namespaces = [];

        foreach ($map as $row) {
            if (substr($row, -1) !== DIRECTORY_SEPARATOR || in_array(trim($row, '/ '), self::$nonModuleFolders, true)) {
                continue;
            }

            $name = trim($row, DIRECTORY_SEPARATOR);

            $namespaces["Bonfire\\{{$name}}"] = [realpath(__DIR__ . "/../{$name}")];
        }

        // Insert the namespaces into the psr4 array in the autoloader
        // to ensure that Bonfire's files get loader prior to vendor files
        $rp = new ReflectionProperty($autoloader, 'prefixes');
        $rp->setAccessible(true);
        $prefixes = $rp->getValue($autoloader);
        $keys     = array_keys($prefixes);

        $prefixesStart = array_slice($prefixes, 0, array_search('Tests\\Support', $keys, true) + 1);
        $prefixesEnd   = array_slice($prefixes, array_search('Tests\\Support', $keys, true) + 1);
        $prefixes      = array_merge($prefixesStart, $namespaces, $prefixesEnd);

        $rp->setValue($autoloader, $prefixes);
    }
}

// This is hacky but will ensure all
// Bonfire namespaces have been registered
// with the system and are found automatically.
Registrar::registerNamespaces();

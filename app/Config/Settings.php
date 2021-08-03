<?php

namespace Config;

use Bonfire\Settings\DatabaseHandler;

class Settings
{
    /**
     * The available handlers. The alias must
     * match a public class var here with the
     * settings array containing 'class'.
     *
     * @var string[]
     */
    public $handlers = ['database'];

    /**
     * Database handler settings.
     */
    public $database = [
        'class' => DatabaseHandler::class,
        'table' => 'settings',
        'writeable' => true,
    ];
}

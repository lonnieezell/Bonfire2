<?php

namespace Config;

use Tatter\Alerts\Config\Alerts as TatterAlerts;

class Alerts extends TatterAlerts
{
    /**
     * Template to use for HTML output.
     *
     * @var string
     */
    public $template = 'Bonfire\\Views\\_alerts';
}

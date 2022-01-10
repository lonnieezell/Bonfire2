<?php

namespace Config;

use Tatter\Alerts\Config\Alerts as TatterAlerts;

class Alerts extends TatterAlerts
{
    // prefix for SESSION variables and HTML classes, to prevent collision
    public $prefix = 'alerts-';

    // Template to use for HTML output
    public $template = 'Bonfire\\Views\\_alerts';

    // Whether to check session flashdata for common alert keys
    public $getflash = true;
}

<?php

namespace Bonfire\Config;

use Tatter\Alerts\Config\Alerts as AlertsConfig;

class Alerts extends AlertsConfig
{
    /**
     * Template to use for HTML output.
     */
    public string $template = 'Bonfire\Views\_alerts.php';

    /**
     * Mapping of Session keys to their CSS classes.
     * Note: Some templates may add prefixes to the class,
     * like Bootstrap "alert-{$class}".
     *
     * @var array<string,string>
     */
    public array $classes = [
        // Bootstrap classes
        'primary'   => 'primary',
        'secondary' => 'secondary',
        'success'   => 'success',
        'danger'    => 'danger',
        'warning'   => 'warning',
        'info'      => 'info',

        // Additional log levels
        'message'   => 'primary',
        'notice'    => 'secondary',
        'error'     => 'danger',
        'critical'  => 'danger',
        'emergency' => 'danger',
        'alert'     => 'warning',
        'debug'     => 'info',

        // Common framework keys
        'messages' => 'primary',
        'errors'   => 'danger',
    ];
}

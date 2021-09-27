<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Users extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Gravatar
     * --------------------------------------------------------------------------
     *
     * If true, will attempt to use gravatar.com for users that do not have
     * avatars saved in the system.
     */
    public $useGravatar = false;

    /**
     * --------------------------------------------------------------------------
     * Gravatar Default image
     * --------------------------------------------------------------------------
     *
     * The default image type to use from Gravatar if they do not have an
     * image for that user.
     */
    public $gravatarDefault = 'blank';

    /**
     * --------------------------------------------------------------------------
     * Avatar Background Colors
     * --------------------------------------------------------------------------
     *
     * The available colors to use for avatar backgrounds.
     */
    public $avatarPalette = [
        '#84705e', '#506da8', '#5b6885', '#7b94b8', '#6c3208',
        '#b97343', '#d6d3ce', '#b392a6', '#af6a76', '#6c6c94',
        '#c38659',
    ];
}

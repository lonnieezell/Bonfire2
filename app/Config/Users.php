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

    /**
     * --------------------------------------------------------------------------
     * Additional User Fields
     * --------------------------------------------------------------------------
     *
     * This lists the additional fields that are available on a user's
     * profile page. They are listed first in groups, where the group name is
     * used on the form's fieldset legend.
     *
     * Each field can have the following values in it's options array:
     *  - label: the input label. If one is not provided, the field name will be used.
     *  - type: the type of HTML input used. Should be the simpler inputs,
     *      like text, number, email, textarea, etc.
     *      Selects, checkboxes, radios, etc are not supported.
     *  - required: true/false
     *  - validation: a validation rules string. If not present will be 'permit_empty|string'
     */
    public $metaFields = [
        //        'Example Fields' => [
        //            'foo' => ['label' => 'Foo', 'type' => 'text', 'required' => true, 'validation' => 'permit_empty|string'],
        //            'Bar' => ['type' => 'text', 'required' => true, 'validation' => 'required|string'],
        //        ],
    ];
}

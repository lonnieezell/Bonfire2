<?php

namespace Config;

use Bonfire\Modules\Users\Libraries\UserRules;
use CodeIgniter\Validation\CreditCardRules;
use CodeIgniter\Validation\FileRules;
use CodeIgniter\Validation\FormatRules;
use CodeIgniter\Validation\Rules;
use Sparks\Shield\Authentication\Passwords\ValidationRules as PasswordRules;

class Validation
{
    //--------------------------------------------------------------------
    // Setup
    //--------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var string[]
     */
    public $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
        PasswordRules::class,
        UserRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    //--------------------------------------------------------------------
    // Rules
    //--------------------------------------------------------------------

    public $users = [
        'email' => 'required|valid_email|unique_email[{id}]',
        'username' => 'required|string|is_unique[users.username,id,{id}]',
        'first_name' => 'permit_empty|string|min_length[3]',
        'last_name' => 'permit_empty|string|min_length[3]',
    ];
}

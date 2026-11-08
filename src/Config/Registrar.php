<?php

namespace Bonfire\Config;

use Bonfire\Actions\Jobs\RunAction;
use Bonfire\Auth\Filters\Admin;
use Bonfire\Consent\Filters\ConsentFilter;
use Bonfire\Content\Fields\BlocksField;
use Bonfire\Content\Fields\EntriesField;
use Bonfire\Content\Fields\ListField;
use Bonfire\Content\Fields\SlugField;
use Bonfire\Content\Fields\TermsField;
use Bonfire\Core\Filters\Boot;
use Bonfire\Core\Filters\OnlineCheck;
use Bonfire\Users\Validation\UserRules;
use Bonfire\View\Decorator;
use CodeIgniter\Shield\Authentication\Passwords\ValidationRules as PasswordRules;
use CodeIgniter\Shield\Filters\ChainAuth;
use CodeIgniter\Shield\Filters\SessionAuth;
use CodeIgniter\Shield\Filters\TokenAuth;
use Config\Filters;
use ReflectionClass;

include_once __DIR__ . '/Constants.php';
include_once __DIR__ . '/../Common.php';

class Registrar
{
    public static function Pager(): array
    {
        return [
            'templates' => [
                'bonfire_full'      => 'Bonfire\Views\_pager_full',
                'bonfire_full_hx'   => 'Bonfire\Views\_pager_full_htmx',
                'bonfire_simple'    => 'Bonfire\Views\_pager_simple',
                'bonfire_simple_hx' => 'Bonfire\Views\_pager_simple_htmx',
                'bonfire_head'      => 'Bonfire\Views\_pager_head',
                'bonfire_list_hx'   => 'Bonfire\Views\_pager_list_htmx',
            ],
        ];
    }

    /**
     * Registers the Shield filters.
     */
    public static function Filters()
    {
        // CodeIgniter currently doesn't support merging
        // nested arrays within the registrars....
        $ref   = new ReflectionClass(Filters::class);
        $props = $ref->getDefaultProperties();

        return [
            'aliases' => [
                'session' => SessionAuth::class,
                'tokens'  => TokenAuth::class,
                'chain'   => ChainAuth::class,
                'online'  => OnlineCheck::class,
                'bonfire' => Boot::class,
                'consent' => ConsentFilter::class,
                'admin'   => Admin::class,
            ],
            'globals' => [
                'before' => array_merge_recursive($props['globals']['before'], [
                    'online' => ['except' => 'site-offline'],
                ]),
                'after' => array_merge_recursive($props['globals']['after'], [
                    'alerts',
                    'consent' => ['except' => ADMIN_AREA . '*'],
                ]),
            ],
            'filters' => [
                'session' => [
                    'before' => [ADMIN_AREA . '*'],
                ],
                'admin' => [
                    'before' => [ADMIN_AREA . '*'],
                ],
                // After 'session' and 'admin', so guests are turned away before modules boot
                'bonfire' => [
                    'before' => ['*'],
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
        ];
    }

    /**
     * Runs queued actions, when codeigniter4/queue is installed.
     */
    public static function Queue()
    {
        return [
            'jobHandlers' => [
                RunAction::NAME => RunAction::class,
            ],
        ];
    }

    /**
     * Permissions added since installs published Config\AuthGroups,
     * granted once by `spark bonfire:permissions`.
     */
    public static function AuthGroups()
    {
        return [
            'permissions' => [
                'users.export'  => "Can download a user's personal data",
                'users.erase'   => "Can erase a user's personal data",
                'email.preview' => 'Can preview Mailables and send the previews to themselves',
                'email.log'     => 'Can read the Mail Outbox',

                'content.access'    => 'Can see the Content menu and the lists of entries',
                'content.edit'      => 'Can create and edit Content entries',
                'content.publish'   => 'Can publish Content entries and set when they go live',
                'content.delete'    => 'Can delete Content entries',
                'content.structure' => 'Can change the structure of Content, such as collections and blueprints',
            ],
            'moduleGrants' => [
                'content' => [
                    'superadmin' => ['content.*'],
                    'admin'      => ['content.*'],
                ],
                'privacy' => [
                    'admin' => ['users.export'],
                ],
                'email' => [
                    'superadmin' => ['email.*'],
                    'admin'      => ['email.preview'],
                ],
            ],
        ];
    }

    /**
     * The field types Content adds to the generic ones in Bonfire\Fields.
     */
    public static function Fields()
    {
        return [
            'moduleTypes' => [
                'slug'    => SlugField::class,
                'entries' => EntriesField::class,
                'terms'   => TermsField::class,
                'blocks'  => BlocksField::class,
                'list'    => ListField::class,
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
}

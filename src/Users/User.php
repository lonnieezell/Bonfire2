<?php

namespace Bonfire\Users;

use Bonfire\Core\Traits\HasMeta;
use CodeIgniter\Shield\Authorization\Groups;
use CodeIgniter\Shield\Entities\User as ShieldUser;

class User extends ShieldUser
{
    use HasMeta;

    /**
     * Necessary property for classes using HasMeta trait,
     * containing the name of the config class with $metaFields property.
     */
    protected string $configClass = 'Users';

    /**
     * Renders out the user's avatar at the specified size (in pixels)
     *
     * @return string
     */
    public function renderAvatar(int $size = 52)
    {
        // Determine the color for the user based on their
        // for existing users, email address since we know we'll always have that
        // Use default hash if new user or the avatar is used as a placeholder

        $idString = 'default-avatar-hash'; // Default avatar string

        if ($this->id) {
            if (setting('Users.avatarNameBasis') === 'name') {
                $idString = $this->first_name ? $this->first_name[0] . ($this->last_name[0] ?? '') : $this->username[0] . $this->username[1];
            } else {
                $idString = $this->email[0] . $this->email[1];
            }
        }

        $idString = mb_strtoupper($idString);

        $idValue = str_split($idString);
        array_walk($idValue, static function (&$char) {
            $char = ord($char);
        });
        $idValue = implode('', $idValue);

        $colors = setting('Users.avatarPalette');

        return view('\Bonfire\Views\_avatar', [
            'user'       => $this,
            'size'       => $size,
            'fontSize'   => 20 * ($size / 52),
            'idString'   => $idString,
            'background' => $colors[$idValue % count($colors)],
        ]);
    }

    /**
     * Generates a link to the user Avatar
     */
    public function avatarLink(?int $size = null): string
    {
        // Default from Gravatar
        if (isset($this->id) && empty($this->avatar) && setting('Users.useGravatar')) {
            $hash = md5(strtolower(trim((string) $this->email)));

            return "https://www.gravatar.com/avatar/{$hash}?" . http_build_query([
                's' => ($size ?? 60),
                'd' => setting(
                    'Users.gravatarDefault',
                ),
            ]);
        }

        return empty($this->avatar)
            ? ''
            : base_url('/uploads/avatars/' . $this->avatar);
    }

    /**
     * Returns the full name of the user.
     */
    public function name(): string
    {
        return trim(implode(' ', [$this->first_name, $this->last_name]));
    }

    /**
     * The name to show for the user: their full name, or their username when they have none.
     */
    public function displayName(): string
    {
        return $this->name() ?: (string) $this->username;
    }

    /**
     * @return string
     */
    public function adminLink(?string $postfix = null)
    {
        $url = ADMIN_AREA . "/users/{$this->id}";

        if ($postfix !== null && $postfix !== '' && $postfix !== '0') {
            $url .= "/{$postfix}";
        }

        return trim(site_url($url));
    }

    /**
     * Returns a list of the groups the user is involved in.
     */
    public function groupsList(): string
    {
        return implode(', ', $this->groupTitles());
    }

    /**
     * Returns the titles of the groups the user is involved in.
     *
     * @return list<string>
     */
    public function groupTitles(): array
    {
        $config = setting('AuthGroups.groups');

        return array_map(static fn (string $group): string => $config[$group]['title'] ?? $group, $this->getGroups());
    }

    /**
     * The title of the first of the user's groups that grants the permission, or null when none does.
     */
    public function groupGranting(string $permission): ?string
    {
        $groups = new Groups();

        foreach ($this->getGroups() ?? [] as $group) {
            $info = $groups->info($group);

            if ($info !== null && $info->can($permission)) {
                return $info->title;
            }
        }

        return null;
    }

    /**
     * The user's status as an `<x-status>` tone and label: banned, pending activation or active.
     *
     * @return array{0: string, 1: string}
     */
    public function status(): array
    {
        return match (true) {
            $this->isBanned()      => ['danger', lang('Users.statusBanned')],
            ! $this->isActivated() => ['warn', lang('Users.statusPending')],
            default                => ['ok', lang('Users.statusActive')],
        };
    }

    /**
     * Returns the validation rules for all User meta fields, if any.
     */
    public function validationRules(?string $prefix = null): array
    {
        return $this->metaValidationRules($prefix);
    }
}

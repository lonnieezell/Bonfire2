<?php

namespace Bonfire\Users;

use Bonfire\Core\Traits\HasMeta;
use CodeIgniter\Shield\Entities\User as ShieldUser;

class User extends ShieldUser
{
    use HasMeta;

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

        $idString = strtoupper($idString);

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
        if (empty($this->avatar)) {
            // Default from Gravatar
            if (setting('Users.useGravatar')) {
                $hash = md5(strtolower(trim($this->email)));

                return "https://www.gravatar.com/avatar/{$hash}?" . http_build_query([
                    's' => ($size ?? 60),
                    'd' => setting(
                        'Users.gravatarDefault'
                    ),
                ]);
            }
        }

        return ! empty($this->avatar)
            ? base_url('/uploads/avatars/' . $this->avatar)
            : '';
    }

    /**
     * Returns the full name of the user.
     */
    public function name(): string
    {
        return trim(implode(' ', [$this->first_name, $this->last_name]));
    }

    /**
     * @return string
     */
    public function adminLink(?string $postfix = null)
    {
        $url = ADMIN_AREA . "/users/{$this->id}";

        if (! empty($postfix)) {
            $url .= "/{$postfix}";
        }

        return trim(site_url($url));
    }

    /**
     * Returns a list of the groups the user is involved in.
     */
    public function groupsList(): string
    {
        $config = setting('AuthGroups.groups');
        $groups = $this->getGroups();

        $out = [];

        foreach ($groups as $group) {
            $out[] = $config[$group]['title'];
        }

        return implode(', ', $out);
    }

    /**
     * Returns the validation rules for all User meta fields, if any.
     */
    public function validationRules(?string $prefix = null): array
    {
        return $this->metaValidationRules('Users', $prefix);
    }
}

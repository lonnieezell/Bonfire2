<?php

namespace App\Entities;

use Sparks\Shield\Entities\User as ShieldUser;

class User extends ShieldUser
{
    /**
     * Renders out the user's avatar at the specified size (in pixels)
     *
     * @param int $size
     *
     * @return string
     */
    public function renderAvatar(int $size=52)
    {
        // Determine the color for the user based on their
        // email address since we know we'll always have that
        $idString = ! empty($this->first_name)
            ? ($this->first_name[0]) . ($this->last_name[0] ?? '')
            : $this->username[0] ?? $this->email[0];
        $idString = strtoupper($idString);

        $idValue = str_split($idString);
        array_walk($idValue, function(&$char) {
            $char = ord($char);
        });
        $idValue = implode('', $idValue);

        $colors = setting('Users.avatarPalette');

        return view('\Bonfire\Views\_avatar', [
            'user' => $this,
            'size' => $size,
            'fontSize' => 20 * ($size / 52),
            'idString' => $idString,
            'background' => $colors[$idValue % count($colors)],
        ]);
    }

    /**
     * Generates a link to the user Avatar
     *
     * @param int|null $size
     *
     * @return string
     */
    public function avatarLink(int $size=null): string
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
            ? base_url('/uploads/avatars/'. $this->avatar)
            : '';
    }

    /**
     * Returns the full name of the user.
     *
     * @return string
     */
    public function name(): string
    {
        return trim(implode(' ', [$this->first_name, $this->last_name]));
    }

    /**
     * @return string
     */
    public function adminLink(string $postfix=null)
    {
        $url = ADMIN_AREA ."/users/{$this->id}";

        if(! empty($postfix)) {
            $url .= "/{$postfix}";
        }

        return trim(site_url($url));
    }

    /**
     * Returns a list of the groups the user is involved in.
     *
     * @return string
     */
    public function groupsList(): string
    {
        $config = setting('AuthGroups.groups');
        $groups = $this->getGroups();

        $out = [];
        foreach($groups as $group) {
            $out[] = $config[$group]['title'];
        }

        return implode(', ', $out);
    }
}

<?php

namespace App\Entities;

use Sparks\Shield\Entities\User as ShieldUser;

class User extends ShieldUser
{
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
            $hash = md5(strtolower(trim($this->email)));
            return "https://www.gravatar.com/avatar/{$hash}?". http_build_query([
                's' => ($size ?? 60),
                'd' => 'retro',
            ]);
        }

        return base_url('/uploads/avatars/'. $this->avatar);
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

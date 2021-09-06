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

        return $this->avatar;
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
            $url .= "{$postfix}";
        }

        return trim(site_url($url));
    }
}

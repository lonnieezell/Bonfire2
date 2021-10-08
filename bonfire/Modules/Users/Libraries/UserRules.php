<?php

namespace Bonfire\Modules\Users\Libraries;

use Sparks\Shield\Models\UserIdentityModel;

class UserRules
{
    /**
     * Checks a user has a unique email within all of the email_password identities.
     *
     * @param string $value
     * @param string $fields
     * @param array  $data
     *
     * @return bool
     */
    public function unique_email(string $value, string $fields, array $data): bool
    {
        return model(UserIdentityModel::class)
            ->where('user_id !=', $fields)
            ->where('type', 'email_password')
            ->where('secret', $value)
            ->countAllResults() === 0;
    }
}

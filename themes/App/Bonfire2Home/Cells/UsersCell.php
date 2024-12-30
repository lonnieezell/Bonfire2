<?php

namespace App\Modules\Bonfire2Home\Cells;

use CodeIgniter\View\Cells\Cell;

class UsersCell extends Cell
{
    public $limit;

    public function render(): string
    {
        $recentUsers = model(\App\Modules\Bonfire2Home\Models\UserModel::class)->getMostRecentUsers($this->limit);

        // Calculate Gravatar URLs
        foreach ($recentUsers as &$user) {
            $emailHash = md5(strtolower(trim($user->email)));
            $user->gravatarUrl = "https://www.gravatar.com/avatar/$emailHash?s=32&d=identicon";
        }

        // Specify the full path to the view file
        return view('\App\Modules\Bonfire2Home\Views\Cells\users.php', ['recentUsers' => $recentUsers]);
    }
}

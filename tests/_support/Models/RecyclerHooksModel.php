<?php

namespace Tests\Support\Models;

use Bonfire\Recycler\Interfaces\CustomRecyclerPurge;
use Bonfire\Recycler\Interfaces\CustomRecyclerQuery;
use Bonfire\Recycler\Interfaces\CustomRecyclerRestore;
use Bonfire\Users\Models\UserModel;

/**
 * A soft-deleting model that records which of the Recycler hooks were used.
 */
class RecyclerHooksModel extends UserModel implements CustomRecyclerQuery, CustomRecyclerRestore, CustomRecyclerPurge
{
    public static array $calls = [];

    public function setupRecycler(): static
    {
        self::$calls[] = 'query';

        return $this->where('username !=', 'hidden');
    }

    public function recyclerRestore(int $id): bool
    {
        self::$calls[] = "restore {$id}";

        return true;
    }

    public function recyclerPurge(int $id): bool
    {
        self::$calls[] = "purge {$id}";

        return true;
    }
}

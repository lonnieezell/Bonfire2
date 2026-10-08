<?php

namespace Tests\Recycler;

use Bonfire\Recycler\Libraries\RecyclableResource;
use Bonfire\Users\Models\UserModel;
use CodeIgniter\Model;
use Tests\Support\Models\RecyclerHooksModel;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class RecyclableResourceTest extends TestCase
{
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        RecyclerHooksModel::$calls = [];
    }

    private function users(): RecyclableResource
    {
        return new RecyclableResource('users', 'Users', UserModel::class, ['username']);
    }

    public function testListsOnlyDeletedRecordsNewestFirst()
    {
        $first  = $this->createUser();
        $second = $this->createUser();
        $this->createUser();

        $model = new UserModel();
        $model->delete($first->id);
        $model->where('id', $first->id)->set(['deleted_at' => '2020-01-01 00:00:00'])->update();
        $model->delete($second->id);

        $deleted = $this->users()->deleted(10);

        $this->assertSame([$second->id, $first->id], array_map(static fn ($row) => (int) $row['id'], $deleted['items']));
        $this->assertSame(2, $deleted['pager']->getTotal());
    }

    public function testRestoreAndPurgeUseDefaults()
    {
        $restored = $this->createUser();
        $purged   = $this->createUser();

        $model = new UserModel();
        $model->delete($restored->id);
        $model->delete($purged->id);

        $this->assertTrue($this->users()->restore($restored->id));
        $this->assertTrue($this->users()->purge($purged->id));

        $this->seeInDatabase('users', ['id' => $restored->id, 'deleted_at' => null]);
        $this->dontSeeInDatabase('users', ['id' => $purged->id]);
    }

    public function testDefaultsUseTheModelsOwnColumns()
    {
        $db    = db_connect();
        $table = $db->prefixTable('recycler_things');
        $db->query("CREATE TABLE IF NOT EXISTS {$table} (thing_id INTEGER PRIMARY KEY, name TEXT, removed_at TEXT NULL)");
        $db->query("INSERT OR REPLACE INTO {$table} (thing_id, name, removed_at) VALUES (7, 'gone', '2020-01-01 00:00:00')");

        $thing = new RecyclableResource('things', 'Things', RecyclerThing::class, ['name']);

        $this->assertSame('thing_id', $thing->primaryKey());
        $this->assertSame([7], array_map(static fn ($row) => (int) $row['thing_id'], $thing->deleted(10)['items']));

        $this->assertTrue($thing->restore(7));
        $this->seeInDatabase('recycler_things', ['thing_id' => 7, 'removed_at' => null]);
    }

    public function testModelHooksReplaceTheDefaults()
    {
        $hidden = $this->createUser(['username' => 'hidden']);
        $other  = $this->createUser();

        $model = new UserModel();
        $model->delete($hidden->id);
        $model->delete($other->id);

        $resource = new RecyclableResource('hooks', 'Hooks', RecyclerHooksModel::class, ['username']);

        $ids = array_map(static fn ($row) => (int) $row['id'], $resource->deleted(10)['items']);

        $this->assertSame([$other->id], $ids);

        $this->assertTrue($resource->restore($other->id));
        $this->assertTrue($resource->purge($other->id));
        $this->assertSame(['query', "restore {$other->id}", "purge {$other->id}"], RecyclerHooksModel::$calls);

        // The hooks ran instead of the defaults: nothing was restored or purged
        $row = db_connect()->table('users')->where('id', $other->id)->get()->getRow();
        $this->assertNotNull($row?->deleted_at);
    }

    public function testColumnsAndLabelAreLocalizedWhenTheLanguageFileHasThem()
    {
        $this->assertSame('Users', $this->users()->label());
        $this->assertSame(['username' => 'Username'], $this->users()->localizedColumns());
    }
}

/**
 * @internal
 */
final class RecyclerThing extends Model
{
    protected $table          = 'recycler_things';
    protected $primaryKey     = 'thing_id';
    protected $allowedFields  = ['name', 'removed_at'];
    protected $useSoftDeletes = true;
    protected $deletedField   = 'removed_at';
}

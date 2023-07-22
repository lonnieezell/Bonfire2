<?php

namespace Tests\Bonfire;

use Bonfire\Core\Traits\Filterable;
use Bonfire\Users\Models\UserFilter;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\Model;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class FilterableTest extends TestCase
{
    use DatabaseTestTrait;

    public function testGetAndSetFilters()
    {
        $filters = [
            'foo' => [
                'title'   => 'Foo',
                'options' => ['bar' => 'baz'],
            ],
        ];

        $model = new UserFilter();

        // Has existing UserFilter array
        $this->assertArrayHasKey('role', $model->getFilters());

        $model->setFilters($filters);
        $this->assertSame($filters, $model->getFilters());
    }

    public function testGetFilterCallsMethodsInOptions()
    {
        $model = new UserFilter();

        $this->assertSame($model->getRoleFilters(), $model->getFilters()['role']['options']);
    }

    public function testFilterMethod()
    {
        $class = new class () extends Model {
            use Filterable;

            protected $table      = 'users';
            protected $returnType = User::class;
            protected $filters    = [
                'status' => [
                    'title'   => 'Status',
                    'options' => ['banned', 'paused'],
                ],
                'active' => [
                    'title'   => 'Active',
                    'options' => [0 => 'Inactive', 1 => 'Active'],
                ],
            ];
        };

        /** @var User $user1 */
        $user1 = fake(UserModel::class, ['status' => 'banned', 'active' => 0]);
        /** @var User $user2 */
        $user2 = fake(UserModel::class, ['status' => 'paused', 'active' => 1]);

        $this->assertSame($user1->id, $class->filter(['status' => 'banned'])->first()->id);
        $this->assertSame($user2->id, $class->filter(['status' => 'paused', 'active' => 1])->first()->id);
        $this->assertNull($class->filter(['status' => 'paused', 'active' => 0])->first());
    }
}

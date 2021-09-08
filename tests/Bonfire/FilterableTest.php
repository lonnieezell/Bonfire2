<?php

namespace Tests\Bonfire;

use App\Models\UserModel;
use Bonfire\Modules\Users\Models\UserFilter;
use Bonfire\Traits\Filterable;
use CodeIgniter\Model;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Entities\User;
use Tests\Support\TestCase;

class FilterableTest extends TestCase
{
    use DatabaseTestTrait;

    public function testGetAndSetFilters()
    {
        $filters = [
            'foo' => [
                'title' => 'Foo',
                'options' => ['bar' => 'baz']
            ]
        ];

        $model = new UserFilter();

        // Has existing UserFilter array
        $this->assertArrayHasKey('role', $model->getFilters());

        $model->setFilters($filters);
        $this->assertEquals($filters, $model->getFilters());
    }

    public function testGetFilterCallsMethodsInOptions()
    {
        $model = new UserFilter();

        $this->assertEquals($model->getRoleFilters(), $model->getFilters()['role']['options']);
    }

    public function testFilterMethod()
    {
        $class = new class() extends Model {
            use Filterable;

            protected $table = 'users';
            protected $returnType = User::class;

            protected $filters = [
                'status' => [
                    'title' => 'Status',
                    'options' => ['banned', 'paused']
                ],
                'active' => [
                    'title' => 'Active',
                    'options' => [0 => 'Inactive', 1 => 'Active']
                ]
            ];
        };

        $user1 = fake(UserModel::class, ['status' => 'banned', 'active' => 0]);
        $user2 = fake(UserModel::class, ['status' => 'paused', 'active' => 1]);

        $this->assertEquals($user1, $class->filter(['status' => 'banned'])->first());
        $this->assertEquals($user2, $class->filter(['status' => 'paused', 'active' => 1])->first());
        $this->assertNull($class->filter(['status' => 'paused', 'active' => 0])->first());
    }
}

<?php

namespace Tests\Support;

use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Faker\Factory;
use Sparks\Shield\Test\AuthenticationTesting;

class TestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use AuthenticationTesting;

    /**
     * When migrations are ran, will ensure
     * all migrations in all modules will run.
     */
    protected $namespace = '';

    /**
     * @var Factory
     */
    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
    }

    /**
     * Creates a simple user with email/password identities.
     */
    protected function createUser(array $params=null)
    {
        $user = fake(UserModel::class, $params);
        $user->createEmailIdentity([
            'email' => $this->faker->email,
            'password' => 'secret123'
        ]);

        return $user;
    }
}

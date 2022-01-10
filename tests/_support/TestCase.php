<?php

namespace Tests\Support;

use App\Entities\User;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Faker\Factory;
use Sparks\Shield\Test\AuthenticationTesting;

/**
 * @internal
 */
abstract class TestCase extends CIUnitTestCase
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();   // @phpstan-ignore-line
    }

    /**
     * Creates a simple user with email/password identities.
     */
    protected function createUser(?array $params = null)
    {
        $email = $params['email']
            ?? $this->faker->email;
        $password = $params['password']
            ?? 'secret123';

        unset($params['email'], $params['password']);

        /**
         * @var User
         */
        $user = fake(UserModel::class, $params);
        $user->createEmailIdentity([
            'email'    => $email,
            'password' => $password,
        ]);

        return $user;
    }
}

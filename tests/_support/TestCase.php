<?php

namespace Tests\Support;

use Bonfire\Bonfire;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\CodeIgniter;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\Mock\MockCodeIgniter;
use Config\App;
use Config\Modules;
use Faker\Factory;

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
        helper('bonfire');
    }

    /**
     * Loads up an instance of CodeIgniter
     * and gets the environment setup.
     *
     * @return CodeIgniter
     */
    protected function createApplication()
    {
        $app = new MockCodeIgniter(new App());
        $app->initialize();

        // Initialize Bonfire so that the BF namespaces get added in
        $bonfire = new Bonfire();
        $bonfire->boot();

        return $app;
    }

    /**
     * Creates a simple user with email/password identities.
     */
    protected function createUser(?array $params = null): User
    {
        $email = $params['email']
            /** @phpstan-ignore-next-line */
            ?? $this->faker->email;
        $password = $params['password']
            ?? 'secret123';

        unset($params['email'], $params['password']);

        /**
         * @var User $user
         */
        $user = fake(UserModel::class, $params);
        $user->createEmailIdentity([
            'email'    => $email,
            'password' => $password,
        ]);

        return $user;
    }
}

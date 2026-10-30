<@php

namespace Tests\Support;

use Bonfire\Core\Namespaces;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

abstract class AdminTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use AuthenticationTesting;

    protected $refresh   = true;
    protected $namespace = null;

    protected function createApplication()
    {
        $app = parent::createApplication();

        // Tests skip the pre_system event, where Bonfire registers its modules
        Namespaces::register();

        // Forget state left over from the previous test
        Services::resetSingle('routes');
        Services::resetSingle('auth');
        Services::resetSingle('settings');

        return $app;
    }

    protected function createUser(array $groups = []): User
    {
        /** @var User $user */
        $user = fake(UserModel::class);
        $user->createEmailIdentity([
            'email'    => 'user' . $user->id . '@example.com',
            'password' => 'secret123',
        ]);

        foreach ($groups as $group) {
            $user->addGroup($group);
        }

        return $user;
    }
}

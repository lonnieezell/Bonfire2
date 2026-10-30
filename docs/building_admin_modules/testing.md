# Testing Your Admin Module

This page shows how to write feature tests for an admin module: tests that request a page as a logged-in user and
check what comes back. By the end you will have tests that check:

- an admin sees the module's list
- a user without the module's permission is turned away
- a guest is sent to the login page
- an htmx request gets back only the part of the page it asked for

The examples test the Blog module from [Tutorial: Build Your First Admin Module](tutorial_first_module.md), up to the
end of step 7. If your module is different, swap in your own routes, permission and seeder.

You need:

- an app with Bonfire installed, using the `phpunit.dist.xml` and `tests/` folder that come with CodeIgniter's app
  starter
- PHPUnit, which the app starter installs as a dev dependency
- some familiarity with CodeIgniter's [Testing](https://codeigniter.com/user_guide/testing/index.html) guide

All paths below are relative to your project root.

## Check the test database

The app starter runs tests against the `tests` group in `app/Config/Database.php`: an SQLite database in memory, with
the table prefix `db_`. You don't need to set anything up. The database starts empty for each test, and the base test
case below runs every migration into it: Shield's, Settings', Bonfire's and your module's.

To run your tests against MySQL or another driver instead, uncomment the `database.tests.*` lines in
`phpunit.dist.xml` and fill them in.

## Write a base test case

Bonfire's own test classes aren't shipped with the package, so give your app a base class for its admin tests. Create
`tests/_support/AdminTestCase.php`:

```php
<?php

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
```

The app starter's `composer.json` already maps `Tests\Support` to `tests/_support`.

Here is what each part does:

- **`DatabaseTestTrait`** migrates the test database. `$refresh = true` rolls it back and migrates it again before
  each test, and `$namespace = null` runs the migrations of every namespace, not only `App`.
- **`FeatureTestTrait`** gives you `get()`, `post()` and `withHeaders()`, which send a request through the router,
  the filters and your controller, and return the response.
- **`AuthenticationTesting`** is Shield's trait. Its `actingAs()` logs a user in for the next request.
- **`Namespaces::register()`** registers each Bonfire module folder, like `src/Users`, and each folder in
  `Config\Bonfire::$appModules`. In a web request this happens on CodeIgniter's `pre_system` event (see
  [How Bonfire Fits Into a Request](../intro/request_flow.md#before-routing-module-namespaces)), but tests never fire
  that event. Without it, Bonfire's migrations and routes aren't found.
- **`Services::resetSingle()`** throws away three shared services, so each test builds them again. `routes` has to be
  rebuilt to pick up Bonfire's routes, which the test bootstrap loaded before the modules were registered. `auth`
  would otherwise keep the user from the last `actingAs()` logged in. `settings` would otherwise keep values saved
  in an earlier test in memory.
- **`createUser()`** makes an active user with an email and password, and adds them to the groups you pass.
  `fake()` fills in a username and name from Bonfire's `UserModel`.

## Create an admin user

Groups come from `app/Config/AuthGroups.php`, not the database, so there's nothing to seed. Add a user to a group
and they get every permission the group's matrix grants:

```php
$admin = $this->createUser(['admin']);
```

Your module's own permissions are different. A module's default grants, its `moduleGrants`, only reach the groups
when someone runs `php spark bonfire:permissions` (see [Permissions](permissions.md#default-grants)). The test
database starts empty, so no group has `posts.list` yet, not even `superadmin`. Give it to your test users in one of
two ways.

**Run the grants command** in `setUp()`. This applies the grants from your module's `Config/Registrar.php`, so the
tests also check that the registrar grants what you meant:

```php
protected function setUp(): void
{
    parent::setUp();

    command('bonfire:permissions');
}
```

The command prints `Added 2 permission grant(s).` among the test results.

**Give one user the permission directly.** Use this when a test is about one user, not about the groups:

```php
$user = $this->createUser(['developer']);
$user->addPermission('posts.list');
```

The user still needs a group that grants `admin.access`, or Bonfire's `admin` filter turns them away before your
controller runs. `developer`, `admin` and `superadmin` all grant it.

## Write the feature test

Create `tests/feature/PostListTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Modules\Blog\Database\Seeds\PostSeeder;
use Tests\Support\AdminTestCase;

/**
 * @internal
 */
final class PostListTest extends AdminTestCase
{
    protected $seed = PostSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        command('bonfire:permissions');
    }

    public function testAdminSeesThePosts(): void
    {
        $admin = $this->createUser(['admin']);

        $result = $this->actingAs($admin)->get('admin/posts');

        $result->assertOK();
        $result->assertSee('Meet the team');
        $result->assertDontSee('A post nobody needs');
    }

    public function testUserWithoutThePermissionIsSentToTheDashboard(): void
    {
        $developer = $this->createUser(['developer']);

        $result = $this->actingAs($developer)->get('admin/posts');

        $result->assertRedirectTo(site_url(ADMIN_AREA));
    }

    public function testGuestIsSentToTheLoginPage(): void
    {
        $result = $this->get('admin/posts');

        $result->assertRedirectTo(url_to('login'));
    }
}
```

`$seed` runs the module's seeder after the migrations, before each test, so every test starts with the same six posts
and one deleted post.

Each test checks a different gate:

- **The admin** gets the page. The `admin` group was granted `posts.list` by the module's registrar, and the list
  leaves out the soft-deleted post.
- **The developer** can enter the admin area, but the Blog registrar grants `posts.list` only to `superadmin` and
  `admin`. The controller's permission check sends them back to the dashboard.
- **The guest** never reaches the controller. Shield's `session` filter sends them to the login page first.

The requests go through the same filters as a real request, so these tests also cover the `admin.access` check and
the module booting. [How Bonfire Fits Into a Request](../intro/request_flow.md#the-filter-chain) lists the filters in
order.

## Test an htmx request

The list's search box asks for `_table` only, by sending an `HX-Request` header. Send the same header to check that the
controller answers with just the table. Add this test to `PostListTest`:

```php
public function testSearchReturnsOnlyTheMatchingRows(): void
{
    $admin = $this->createUser(['admin']);

    $result = $this->actingAs($admin)
        ->withHeaders(['HX-Request' => 'true'])
        ->get('admin/posts?search=team');

    $result->assertOK();
    $result->assertSee('Meet the team');
    $result->assertDontSee('Hello, world');
    $result->assertDontSeeElement('input[name=search]');
}
```

The last assertion checks that the search box, which is part of the full page, isn't in the response.

!!! note
    Don't check for a missing `<html>` tag to prove a response is a partial. `assertSee()`, `assertDontSee()` and
    `getBody()` read the response through CodeIgniter's `DOMParser`, which wraps a fragment in `<html><body>`.

## Run the tests

```
vendor/bin/phpunit tests/feature/PostListTest.php
```

All four tests pass. Leave off the path to run the whole suite.

## Troubleshooting

### `table db_users has no column named first_name`

Bonfire's migrations didn't run, because its module namespaces weren't registered. Check that your test extends
`AdminTestCase` and that `createApplication()` calls `Namespaces::register()`.

### `The route for "login" cannot be found`

Bonfire's routes weren't loaded. The same goes for any other Bonfire route, like `search`. `createApplication()` must
reset the `routes` service after registering the namespaces.

### A guest isn't sent to the login page

A user from an earlier test is still logged in, so the request gets past Shield's `session` filter. Reset the `auth` service in `createApplication()`.

### An admin is redirected to the dashboard

The user doesn't have your module's permission. Run `bonfire:permissions` in `setUp()` or call `addPermission()`. Also
check that CodeIgniter finds your module's `Config/Registrar.php`: a module in `app/Modules` needs its namespace in
`app/Config/Autoload.php` (see [Permissions](permissions.md#declaring-permissions)).

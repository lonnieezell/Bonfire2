# Make Your Module's Data Exportable and Erasable

When an admin exports or erases a user's data, Bonfire asks every registered provider for its part. A module that
stores personal data and registers no provider is left out: its data isn't in the export and survives an erasure. This
guide adds a provider to a forum module whose posts and uploaded attachments belong to users. For the service and
config, see the [Privacy](../modules/privacy.md) reference.

## 1. Export the module's data

Implement `Bonfire\Privacy\PersonalDataProvider`. `export()` returns everything the module holds about the user, as an
array that can be written as JSON. Include every personal field, like IP addresses and signatures, not only the
content. Leave out secrets such as password hashes, tokens or API keys.

```php
namespace App\Modules\Forum;

use App\Modules\Forum\Models\PostModel;
use Bonfire\Privacy\PersonalDataProvider;
use Bonfire\Users\User;

class ForumPersonalData implements PersonalDataProvider
{
    public function label(): string
    {
        return 'Forum';
    }

    public function export(User $user): array
    {
        return model(PostModel::class)
            ->select('id, title, body, signature, attachment, ip_address, created_at')
            ->where('user_id', $user->id)
            ->asArray()
            ->findAll();
    }

    public function erase(User $user): void
    {
        // Step 2
    }
}
```

## 2. Erase it: delete or anonymise

Each provider decides what erasing means for its data.

- **Delete** what only matters to the user, like saved searches, drafts or private messages they sent.
- **Anonymise** what other people rely on, like forum replies others quote or orders your accounts need. Clear the
  personal fields and keep the rest. The `users` row itself stays, named `deleted-user-{id}`, so a `user_id` still
  points somewhere.

Forum posts are part of other people's threads, so this provider anonymises them: it clears the signature, the IP
address and the attachment, and keeps the post.

Files are the exception to "everything in one transaction", because a deleted file can't be rolled back. Queue file
deletions with `service('personalData')->afterErase()`, which runs them only after every provider has succeeded and the
transaction has committed:

```php
public function erase(User $user): void
{
    $posts = model(PostModel::class);
    $files = array_filter(array_column(
        $posts->select('attachment')->where('user_id', $user->id)->asArray()->findAll(),
        'attachment',
    ));

    $posts->where('user_id', $user->id)
        ->set(['signature' => null, 'attachment' => null, 'ip_address' => null])
        ->update();

    service('personalData')->afterErase(static function () use ($files): void {
        foreach ($files as $file) {
            @unlink(WRITEPATH . 'uploads/forum/' . $file);
        }
    });
}
```

Don't catch your own exceptions in `erase()`. Throwing rolls back every provider's changes for that user, and the
admin sees that the erasure failed.

## 3. Register it

Register the provider from your module's `initAdmin()`, under an alias that names your module. The alias is the
provider's key in the export.

```php
public function initAdmin()
{
    service('personalData')->register('forum', new ForumPersonalData());
}
```

Bonfire's own `users` provider always runs last, so your provider can still read the user's email and other details
while it erases.

`initAdmin()` only runs on admin pages, the prune command and queued actions. A site that offers self-service export
or erasure on its front end boots the admin modules first; see
[Let users do it themselves](../building_sites/data_requests.md#5-optional-let-users-do-it-themselves).

## 4. Prune data that expires

If your module keeps personal data only for a while, like IP addresses in an audit log, also implement
`Bonfire\Privacy\PrunesPersonalData`. `php spark bf:privacy:prune`, which Bonfire schedules daily, calls `prune()` and
reports the number you return: how many records it deleted or cleared.

```php
use Bonfire\Privacy\PrunesPersonalData;
use CodeIgniter\I18n\Time;

class ForumPersonalData implements PersonalDataProvider, PrunesPersonalData
{
    // ...

    public function prune(): int
    {
        model(PostModel::class)
            ->where('created_at <', Time::now()->subDays(30)->toDateTimeString())
            ->where('ip_address IS NOT NULL')
            ->set(['ip_address' => null])
            ->update();

        return db_connect()->affectedRows();
    }
}
```

If the period should be configurable, give your module its own config value rather than adding keys to
`Config\Privacy::$retention`.

## 5. Test the provider

Test through the service, the way an admin's export and erasure use it. With the base class from
[Testing](testing.md):

```php
use App\Modules\Forum\ForumPersonalData;
use App\Modules\Forum\Models\PostModel;
use Config\Services;
use Tests\Support\AdminTestCase;

final class ForumPersonalDataTest extends AdminTestCase
{
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('personalData');
        service('personalData')->register('forum', new ForumPersonalData());
    }

    public function testExportsTheUsersPosts(): void
    {
        $user = $this->createUser();
        model(PostModel::class)->insert(['user_id' => $user->id, 'title' => 'Hello', 'body' => 'First post']);

        $export = service('personalData')->export($user);

        $this->assertSame(['Hello'], array_column($export['forum'], 'title'));
    }

    public function testErasingKeepsThePostsWithoutPersonalDetails(): void
    {
        $user = $this->createUser();
        model(PostModel::class)->insert(['user_id' => $user->id, 'title' => 'Hello', 'ip_address' => '10.0.0.1']);

        service('personalData')->erase($user);

        $this->seeInDatabase('forum_posts', ['user_id' => $user->id, 'title' => 'Hello', 'ip_address' => null]);
    }
}
```

Registering only your provider keeps the test about your module. Register `Bonfire\Users\UserPersonalData` too when
you want to see the whole erasure, users row included.

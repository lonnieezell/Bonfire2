# Handle a Data Export or Erasure Request

Under the GDPR, people can ask for a copy of the data you hold about them, or ask you to erase it. This guide walks an
admin through answering both requests in Bonfire, and keeping old personal data from piling up. It ends with how to
let users do it themselves, if your site wants that. For the service, settings and command, see the
[Privacy](../modules/privacy.md) reference.

## 1. Check who is asking

Before you send or erase anything, make sure the request comes from the person the data is about. Bonfire leaves this
to you, because the right check depends on your site. Common ones:

- the request comes from the email address on the account;
- the person confirms it while signed in;
- for sensitive data, you ask for something only they would know.

You have one month to answer a request. Keep a note of what you checked and when.

## 2. Export the user's data

You need the `users.export` permission, which the `superadmin` and `admin` groups have by default. Only users with
`users.manage-admins` can export users who manage admins.

1. In the admin area, open **Users** and click the user.
2. Click **Export data** at the top of their edit screen.

Your browser downloads `user-{id}-data.json`. It holds one section per module that stores personal data:

- `users`: the account, email address, meta, avatar file name, sign-in identities and login history;
- `consent`: the cookie consent they gave while signed in;
- one section for every other module that registered a provider.

Password and token hashes are never included.

Send the file to the person through a channel you trust. Each export is logged as a `notice` with your user ID and
theirs.

## 3. Erase the user's data

You need the `users.erase` permission, which only the `superadmin` group has by default. You can't erase yourself, and
only users with `users.manage-admins` can erase users who manage admins.

1. Open **Users**.
2. Tick the users in the list, or open the ⋯ menu on a user's row.
3. Choose **Erase personal data**.
4. Type the number of users to confirm, and click **Erase personal data**.

Each user is erased in one transaction across every module: either all of their data is erased or, if something
fails, none of it is. The run is recorded in the `action_runs` table, which is your record that the erasure happened.
The **Action runs** dashboard widget shows failed runs, and finished ones too when its **Include completed runs**
option is on.

### What erasure keeps

Bonfire anonymises the account instead of deleting it:

- The user row stays, named `deleted-user-{id}`, with no name, email address or avatar. It can't sign in. Anything on
  your site that points at the user, like posts or orders, keeps working and shows the anonymous name.
- Login attempts stay for security audits, with the email address replaced. Their IP addresses are deleted when
  retention prunes them, after 90 days by default.

Erased users don't appear in the Recycler, and can't be restored or purged. Whether a module deletes its records or
anonymises them is up to that module; see
[Make Your Module's Data Exportable and Erasable](../building_admin_modules/personal_data.md).

## 4. Make sure old data is deleted on schedule

Bonfire deletes login attempts and old consent records once they are past their retention period. It does this with a
daily `bf-privacy-prune` task that it schedules through [CodeIgniter Tasks](https://tasks.codeigniter.com/).

1. Register Bonfire's tasks in `init()` in `app/Config/Tasks.php`. `php spark bf:install` sets this up on new installs.
   On an older install without the file, run `php spark tasks:publish`, delete the example tasks it adds, and add the
   call:

    ```php
    public function init(Scheduler $schedule)
    {
        \Bonfire\Bonfire::schedule($schedule);
    }
    ```

2. Check that the task is scheduled:

    ```
    php spark tasks:list
    ```

    The list includes `bf-privacy-prune`, with its next run at midnight.

3. Turn on the CodeIgniter Tasks runner, if your site doesn't run it already. CodeIgniter Tasks runs every scheduled
   task, Bonfire's and your own, from one command that your server calls every minute. With cron, that is a single
   entry:

    ```
    * * * * * cd /path/to/your/site && php spark tasks:run >> /dev/null 2>&1
    ```

    If your host has its own scheduler, point it at `php spark tasks:run` instead. See the
    [CodeIgniter Tasks documentation](https://tasks.codeigniter.com/) for details.

4. To change how long data is kept, edit `$retention` in `app/Config/Privacy.php`: a number of days, or `null` to keep
   the data forever. `php spark bf:install` publishes this file on new installs. On an older install, create it:

    ```php
    <?php

    namespace Config;

    use Bonfire\Privacy\Config\Privacy as BonfirePrivacy;

    class Privacy extends BonfirePrivacy
    {
        public array $retention = [
            'logins'      => 90,
            'tokenLogins' => 90,
            'consents'    => 365,
        ];
    }
    ```

To prune straight away, run `php spark bf:privacy:prune`. **Tools → System Info** warns you when pruning hasn't run
for over a week.

## 5. Optional: let users do it themselves

Bonfire doesn't ship "Download my data" or "Delete my account" pages, but `service('personalData')` is public, so you can
build them in your own front end. Modules register their providers from `initAdmin()`, which doesn't run on front-end
pages, so boot the admin modules first.

Add the routes behind the `session` filter, so only signed-in users reach them:

```php
// app/Config/Routes.php
$routes->group('my-data', ['filter' => 'session'], static function ($routes) {
    $routes->get('download', 'MyData::download');
    $routes->post('erase', 'MyData::erase');
});
```

Then the controller. Erasure can't be undone, so it asks for the user's password first:

```php
namespace App\Controllers;

use Bonfire\Bonfire;
use Bonfire\Privacy\PersonalData;

class MyData extends BaseController
{
    public function download()
    {
        $user = auth()->user();
        log_message('notice', 'User {user} exported their personal data.', ['user' => $user->id]);

        return $this->response
            ->setHeader('Content-Disposition', 'attachment; filename="my-data.json"')
            ->setJSON($this->personalData()->export($user));
    }

    public function erase()
    {
        $user    = auth()->user();
        $correct = auth()->check([
            'email'    => $user->email,
            'password' => (string) $this->request->getPost('password'),
        ])->isOK();

        if (! $correct) {
            return redirect()->back()->with('error', 'That password is not correct.');
        }

        // Self-service erasures don't go through an admin action, so log them for your records
        log_message('notice', 'User {user} erased their personal data.', ['user' => $user->id]);

        auth()->logout();
        $this->personalData()->erase($user);

        return redirect()->to('/')->with('message', 'Your data has been erased.');
    }

    private function personalData(): PersonalData
    {
        if (service('personalData')->providers() === []) {
            (new Bonfire())->boot(admin: true);
        }

        return service('personalData');
    }
}
```

The erase form posts a `password` field to `my-data/erase`, with `csrf_field()` if CSRF protection is on.

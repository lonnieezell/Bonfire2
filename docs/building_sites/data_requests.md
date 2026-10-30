# Handle a Data Export or Erasure Request

Under the GDPR, people can ask for a copy of the data you hold about them, or ask you to erase it. This guide walks an
admin through answering both requests in Bonfire, and shows how to let users do it themselves if your site wants that.
For the service, settings and command, see the [Privacy](../modules/privacy.md) reference.

## 1. Check who is asking

Before you send or erase anything, make sure the request comes from the person the data is about. Bonfire leaves this
to you, because the right check depends on your site. Common ones:

- the request comes from the email address on the account;
- the person confirms it while signed in;
- for sensitive data, you ask for something only they would know.

You have one month to answer a request. Keep a note of what you checked and when.

## 2. Export the user's data

1. In the admin area, open **Users** and click the user.
2. Click **Export data** at the top of their edit screen.

Your browser downloads `user-{id}-data.json`. It holds one section per module that stores personal data: `users` for
the account, meta, avatar, sign-in identities and login history, `consent` for the cookie consent they gave while
signed in, and a section for every module that registered its own provider. Password and token hashes are never
included.

Send the file to the person through a channel you trust. The export is logged as a `notice` with your user ID and
theirs.

You need the `users.export` permission, which the `superadmin` and `admin` groups have by default.

## 3. Erase the user's data

1. Open **Users**.
2. Tick the user, or several, and choose **Erase personal data** from the bulk actions. For one user, you can use the
   action on their row.
3. Type the number of users to confirm.

Every module that registered a provider erases its data in one transaction, so either everything is erased or, if
something fails, nothing is. The run appears in the **Action runs** dashboard widget and in the `action_runs` table,
which is your record that the erasure happened.

You need the `users.erase` permission, which only the `superadmin` group has by default. You can't erase yourself.

### What erasure keeps

Bonfire anonymises the account instead of deleting it:

- The user row stays, named `deleted-user-{id}`, with no name, email address or avatar. It can't sign in. Anything on
  your site that points at the user, like posts or orders, keeps working and shows the anonymous name.
- Login attempts stay for security audits, with the email address replaced. Their IP addresses are deleted when
  retention prunes them, after 90 days by default.

Erased users can't be restored from the Recycler. If you need to delete a module's records outright rather than
anonymise them, that module's provider decides; see
[Make Your Module's Data Exportable and Erasable](../building_admin_modules/personal_data.md).

## 4. Make sure old data is deleted on schedule

Bonfire deletes login attempts and old consent records once they are past their retention period. This only happens
if the scheduler runs.

1. Check that `app/Config/Tasks.php` calls Bonfire's schedule in `init()`. `php spark bf:install` adds it:

    ```php
    \Bonfire\Bonfire::schedule($schedule);
    ```

2. Add a cron entry that runs the scheduler every minute:

    ```
    * * * * * cd /path/to/your/site && php spark tasks:run >> /dev/null 2>&1
    ```

3. To change how long data is kept, publish the Privacy config (`php spark bf:install` does this) and edit
   `$retention` in `app/Config/Privacy.php`. Days, or `null` to keep data forever.

**Tools → System Info** warns you when pruning hasn't run for over a week. To prune straight away, run
`php spark bf:privacy:prune`.

## Letting users do it themselves

Bonfire doesn't ship "Download my data" or "Delete my account" pages, but `service('personalData')` is public, so you can
build them in your own front end. The providers register themselves from `initAdmin()`, which doesn't run on front-end
pages, so boot the admin modules first:

```php
namespace App\Controllers;

use Bonfire\Bonfire;

class MyData extends BaseController
{
    public function download()
    {
        $data = $this->personalData()->export(auth()->user());

        return $this->response
            ->setHeader('Content-Disposition', 'attachment; filename="my-data.json"')
            ->setJSON($data);
    }

    public function erase()
    {
        // Ask for the password again before this point
        $user = auth()->user();

        auth()->logout();
        $this->personalData()->erase($user);

        return redirect()->to('/')->with('message', 'Your data has been erased.');
    }

    private function personalData()
    {
        if (service('personalData')->providers() === []) {
            (new Bonfire())->boot(admin: true);
        }

        return service('personalData');
    }
}
```

Protect both routes with the `session` filter, and make the person confirm an erasure, for example by entering their
password, since it can't be undone.

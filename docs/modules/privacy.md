# Privacy

The Privacy module collects the personal data every module holds about a user, so an admin can export or erase it in
one step, and deletes personal data that is past its retention period. It backs the **Export data** button on a user's
edit screen and the **Erase personal data** action on the users list.

For step-by-step guides, see [Handle a Data Export or Erasure Request](../building_sites/data_requests.md) and
[Make Your Module's Data Exportable and Erasable](../building_admin_modules/personal_data.md).

## The `personalData` service

`service('personalData')` returns the shared `Bonfire\Privacy\PersonalData` instance. Modules register their providers
with it from `initAdmin()`.

| Method | What it does |
|---|---|
| `register(string $alias, PersonalDataProvider $provider)` | Adds a provider. Returns the service, so calls can be chained. A second provider with the same alias replaces the first. |
| `providers()` | The registered providers, keyed by alias. |
| `export(User $user): array` | Every provider's `export()`, keyed by alias. |
| `erase(User $user): void` | Runs every provider's `erase()` in one database transaction, the `users` provider last. If any provider throws, the transaction is rolled back and the exception is rethrown. |
| `afterErase(Closure $callback): void` | Queues work, like deleting a file, to run once the current erasure has committed. Dropped if it rolls back. |
| `prune(): array` | Runs `prune()` on every provider that implements `PrunesPersonalData`, records the time, and returns the counts keyed by alias. |
| `lastPruned(): ?Time` | When `prune()` last ran, or `null` if it never has. |

Bonfire registers two providers:

- **`users`**: the account row, email address, meta, avatar file name, sign-in identities (without password or token
  hashes), login attempts and access token logins. Erasing anonymises the user (see below). Pruning deletes old login
  rows.
- **`consent`**: the consent records saved while the user was signed in. Erasing deletes them. Pruning deletes old
  records (see [Retention](#retention)).

## Interfaces

**`Bonfire\Privacy\PersonalDataProvider`**

```php
interface PersonalDataProvider
{
    public function label(): string;
    public function export(User $user): array;
    public function erase(User $user): void;
}
```

**`Bonfire\Privacy\PrunesPersonalData`**

```php
interface PrunesPersonalData
{
    /**
     * @return int How many records were deleted
     */
    public function prune(): int;
}
```

A provider implements both when its data expires.

## What erasing a user does

Erasure anonymises the user rather than deleting the row, so content in other tables that points at the user keeps
working. The `users` provider:

- replaces `username` with `deleted-user-{id}` and clears `first_name`, `last_name` and `avatar`;
- deletes the avatar file (after the transaction commits), the user's meta, Shield identities and remember tokens;
- deactivates and soft-deletes the account, and sets `erased_at`;
- keeps rows in `auth_logins` and `auth_token_logins` for security audits, but replaces their `identifier` (usually the
  email address) with `deleted-user-{id}`.

Erased users don't appear in the Recycler and can't be restored or purged. See
[ADR 0006](https://github.com/lonnieezell/Bonfire2/blob/develop/docs/adr/0006-erasure-anonymises-users.md) for the
reasoning.

## Permissions

| Permission | Lets a user | Default groups |
|---|---|---|
| `users.export` | download a user's data | `superadmin` (through `users.*`), `admin` |
| `users.erase` | run **Erase personal data** | `superadmin` (through `users.*`) |

Only users with `users.manage-admins` can export or erase users who have it, and nobody erases themselves. These are
the same rules as deleting. Every erasure is recorded in `action_runs`, and every export is logged as a `notice`.

## Retention

`Config\Privacy::$retention` sets how many days data is kept. `null` keeps it forever.

```php
public array $retention = [
    'logins'      => 90,
    'tokenLogins' => 90,
    'consents'    => 365,
];
```

- **logins**: rows in `auth_logins`, including their IP addresses and user agents.
- **tokenLogins**: rows in `auth_token_logins`.
- **consents**: rows in `consent_records` older than this many days. Each browser's latest record is kept for as long
  as its consent is still valid (`Consent::$consentLength`); the older answers it replaced are deleted.

Erased users' login rows keep their IP addresses until they are pruned. If you set `logins` to `null`, those IP
addresses are kept for good.

## Scheduling

```
php spark bf:privacy:prune
```

deletes the expired data and prints how many records each provider removed. Bonfire schedules it to run daily through
[CodeIgniter Tasks](https://tasks.codeigniter.com/). `php spark bf:install` adds this line to `init()` in
`app/Config/Tasks.php`, creating the file if needed:

```php
\Bonfire\Bonfire::schedule($schedule);
```

For scheduled tasks to run, call `php spark tasks:run` every minute from cron:

```
* * * * * cd /path/to/your/site && php spark tasks:run >> /dev/null 2>&1
```

**Tools → System Info** warns when the prune has never run, or last ran more than 7 days ago.

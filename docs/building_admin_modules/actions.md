# Row and Bulk Actions

An action is something an admin can do to one record from its row's menu, or to every checked record of a list from
the selection bar: delete, ban, publish, export. Bonfire runs every action through one endpoint that checks the
permission and the CSRF token, asks for confirmation when the action wants it, skips records the action may not touch,
and runs large batches in the background when a queue is available.

The Users list (row and bulk delete) and the Logs list (bulk delete) are built this way.

## Writing An Action

An action is one class that extends `Bonfire\Actions\Action`:

```php
use Bonfire\Actions\Action;
use Bonfire\Actions\ActionContext;
use Bonfire\Actions\ActionResult;
use Bonfire\Actions\Confirm;
use Bonfire\Actions\Placement;
use Bonfire\Users\User;

class BanUsers extends Action
{
    public string    $label      = 'Ban';
    public string    $icon       = 'fas fa-ban';
    public string    $permission = 'users.manage-admins';
    public Placement $placement  = Placement::Both;
    public bool      $danger     = true;

    public function confirm(): ?Confirm
    {
        return Confirm::make('Ban {count} users?')
            ->body('They are signed out and cannot sign in until unbanned.')
            ->button('Ban');
    }

    public function allows(User $actor, object $record): bool
    {
        return $actor->id !== $record->id;
    }

    public function handle(ActionContext $context): ActionResult
    {
        foreach ($context->entities() as $user) {
            $user->ban();
        }

        return ActionResult::message('{count} users banned.');
    }
}
```

**label** and **icon** (a Font Awesome class) are shown on the action's button.

**permission** is needed to see and run the action. Leave it empty for anyone who can reach the admin area. Add a new
permission to your app's `AuthGroups` config.

**placement** is where the action is offered: `Placement::Row` in each row's menu, `Placement::Bulk` in the selection
bar, or `Placement::Both`.

**danger** styles the action as destructive, in red.

**confirm()** returns what to ask before the action runs, or `null` to run straight away. `{count}` in the title
becomes the number of records. `->typeToConfirm()` makes the admin type that number before a large destructive run.

**allows()** decides, per record, whether the action may touch it. Records it rejects, and selected records that no
longer exist, are skipped and reported ("3 users banned. Skipped 1.") rather than failing the batch.

**handle()** does the work and returns an `ActionResult::message()`, or `ActionResult::error()` when it fails. `{count}`
in the message becomes the number of records handled. A thrown exception is logged, and the admin sees an
"Unknown error" message.

The action's name in its URL comes from the class name: `BanUsers` is `ban-users`.

!!! warning

    `handle()` may run in a queue worker, where there is no request, session or signed-in user. Use only what the
    context gives you: `$context->entities()` (the records, one at a time), `$context->chunks()` (the records, a chunk
    of up to 100 at a time) and `$context->actor()` (the admin who started the run).

### Deleting

`Bonfire\Actions\DeleteAction` is a ready-made delete: a red **Delete** that asks first and deletes a chunk at a time.
Extend it with how your records are deleted, and usually your own permission and wording:

```php
use Bonfire\Actions\DeleteAction;

class DeletePhotos extends DeleteAction
{
    public string $permission = 'photos.delete';

    protected function delete(array $records): void
    {
        model(PhotoModel::class)->delete(array_map(static fn ($photo) => $photo->id, $records));
    }
}
```

Override `confirm()` for your own question and `deleted()` for the message once it's done. See
`Bonfire\Users\Actions\DeleteUsers` for a complete example.

## Registering Actions

Actions belong to a named list. Register them in your module's `initAdmin()`, saying where the list's records come
from:

```php
use Bonfire\Actions\ModelSource;

public function initAdmin()
{
    service('actions')->for('photos')
        ->records(new ModelSource(PhotoModel::class))
        ->add(new DeletePhotos());
}
```

`ModelSource` finds records by primary key through a model. For records that don't live in a model, implement
`Bonfire\Actions\RecordSource`, which has one method, `find(array $ids): array`; the Logs list's `LogStore` is an
example.

Any module may add actions to any list, including Bonfire's own. These lists exist:

| List    | Records                                    |
|---------|--------------------------------------------|
| `users` | `Bonfire\Users\User`                       |
| `logs`  | Objects whose `id` is a log file's name    |

```php
service('actions')->for('users')->add(new BanUsers());
```

## Showing Actions In A List

Two view components render a list's actions for the signed-in admin, leaving out those they lack the permission for.

`<x-row-actions>` renders a row's menu. Pass the list and the record's ID. Anything in its slot, like an Edit link,
comes first:

```php
<td class="text-end">
    <x-row-actions list="photos" id="<?= $photo->id ?>">
        <li><a href="<?= url_to('photo-edit', $photo->id) ?>" class="dropdown-item">Edit</a></li>
    </x-row-actions>
</td>
```

The row menu shows every row action the admin has the permission for; `allows()` is checked when the action runs.

`<x-bulk-actions>` goes inside `<x-selection-bar>`, in a form whose row checkboxes are named `selects[]` with the
record's ID as their value:

```php
<form method="post">
    <?= csrf_field() ?>
    <table class="table bf-table">
        <!-- ... -->
        <td class="bf-select-cell">
            <input type="checkbox" name="selects[]" value="<?= $photo->id ?>" class="form-check-input">
        </td>
        <!-- ... -->
    </table>

    <x-selection-bar>
        <x-bulk-actions list="photos" />
    </x-selection-bar>
</form>
```

## Confirmation

With JavaScript, an action that asks first opens `<x-confirm-dialog>`, a dialog that is in the Admin layout once. It
shows the title with the count filled in, the body, and a red button for dangerous actions.

Without JavaScript, the action's button posts straight to the endpoint, which answers with a confirmation page holding
the same question and a button to go ahead. Either way, an action never runs until it's confirmed.

The dialog also handles two other kinds of confirmation, so they look the same:

- htmx's `hx-confirm` attribute: `<a hx-get="..." hx-confirm="Remove the image?">`.
- A `data-confirm` attribute on any link or submit button: the link is followed, or the form submitted, once the admin
  confirms. `data-confirm-button` sets the button's label and `data-confirm-danger="1"` makes it red.

```php
<a href="<?= url_to('photo-purge', $photo->id) ?>" data-confirm="Purge this photo?" data-confirm-danger="1">Purge</a>
```

## Running In The Background

Each action decides whether its runs are queued with its `$queue` property:

- `Queue::Auto` (the default) runs in the request, unless it has more records than `$queueAbove` (500 by default).
- `Queue::Always` always queues.
- `Queue::Never` always runs in the request.

```php
use Bonfire\Actions\Queue;

class ExportPhotos extends Action
{
    public Queue $queue = Queue::Always;
    // ...
}
```

Queued runs need the [codeigniter4/queue](https://github.com/codeigniter4/queue) package, which Bonfire suggests but
doesn't require:

```console
composer require codeigniter4/queue
php spark migrate --all
php spark queue:work bonfire-actions
```

Bonfire registers its `bonfire-action` job with the queue for you. Keep a `queue:work bonfire-actions` worker running,
or queued runs will wait forever.

Without the package, `Queue::Auto` runs in the request, a chunk at a time, and `Queue::Always` refuses with a message
explaining what's missing.

A queued run is recorded in the `action_runs` table with its totals and status. The admin who started it sees a toast
that shows its progress, updated every two seconds, and its message once it ends.

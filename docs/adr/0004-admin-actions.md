# ADR 0004: One action system for row and bulk actions, with confirmation and queueing

- **Status:** Accepted
- **Date:** 2026-10-28

## Context

Admin lists have no shared way to act on records:

- **Bulk actions are hard-coded per list.** The Users table is a form posting
  to `users/delete-batch`, handled by `UserController::deleteBatch()`; Logs
  does the same its own way. Delete is the only bulk action anywhere, and a
  module cannot add an action to a list it does not own.
- **Confirmation is the browser's `confirm()`,** written inline as `onclick`
  in four views, plus one `hx-confirm` in `Users/Views/_avatar.php`. It is
  unstyled, cannot show what is affected, and without JavaScript there is no
  confirmation at all, though `<x-selection-bar>` is built to work without it.
- **Row delete is a GET.** `users/(:num)/delete` is linked from
  `Users/Views/_row_info.php`. CodeIgniter's CSRF filter checks only POST, so a
  link on another site can delete a user while an admin is signed in.
- **Nothing is queued.** Every action runs inside the request, so acting on
  thousands of records risks timeouts.

ADR 0003 gives resources an `actions()` list but does not define what an action
is. Hand-written modules need actions too.

## Decision

**An action is one class.** `Bonfire\Actions\Action` declares a label, icon,
permission, placement (`Row`, `Bulk` or `Both`), whether it is dangerous, an
optional confirmation, optional input fields, a per-record check and a handler:

```php
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

    public function fields(): array
    {
        return [Textarea::make('reason')->rules('permit_empty|max_length[255]')];
    }

    public function allows(User $actor, object $entity): bool
    {
        return $actor->id !== $entity->id;
    }

    public function handle(ActionContext $ctx): ActionResult
    {
        foreach ($ctx->entities() as $user) {
            $user->ban($ctx->input('reason'));
        }

        return ActionResult::message('{count} users banned.');
    }
}
```

`fields()` uses the field classes from ADR 0003. `ActionContext::entities()`
yields records in chunks, so the same `handle()` serves a request and a queued
job.

**Actions are registered against a named list.**
`service('actions')->for('users')->add(new BanUsers())` from `initAdmin()`.
Any module may add actions to any list. A resource passes its `actions()` to
the registry, so resources build on this system rather than the other way
round, and it ships first.

**One endpoint runs every action, over POST only.**

```
POST admin/actions/{list}/{action}           run (ids[], inputs, CSRF)
GET  admin/actions/{list}/{action}/confirm   confirmation page without JavaScript
GET  admin/actions/runs/{id}                 progress fragment for a queued run
```

`ActionController` checks the permission, validates `fields()`, loads the IDs,
drops records `allows()` rejects, then runs or queues the action. Rejected
records are skipped and reported ("Banned 8 users, skipped 1"), not a reason to
fail the batch.

**Three components render actions.**

- `<x-row-actions list="users" :entity="$user">` renders a row's actions as a
  dropdown.
- `<x-bulk-actions list="users">` sits inside `<x-selection-bar>` and posts the
  checked `selects[]`.
- `<x-confirm-dialog>` sits once in the Admin layout: a native `<dialog>`
  driven by Alpine that shows the title with `{count}` filled in, the body,
  any `fields()`, and a danger-styled button. It handles htmx's `htmx:confirm`
  event, so existing `hx-confirm` attributes get the same dialog. An optional
  `->typeToConfirm()` asks for the record count before a large destructive
  run.

Without JavaScript, action buttons go to the `/confirm` page, which renders the
same content as a page with a submit button.

**Queueing is opt-in per action.** `public Queue $queue` is `Never`, `Auto`
(the default: run in the request below a threshold, queue above it) or
`Always`. Queued runs use `codeigniter4/queue`, a suggested dependency, and need
`spark queue:work`. Without it installed, `Auto` runs in the request in chunks
and `Always` refuses with an explanation.

**Queued runs are tracked.** An `action_runs` table records the action, list,
user, IDs, inputs, totals (processed, skipped, failed), status, error and
timestamps. The job updates the row after each chunk. The request returns
straight away, and a toast polls `runs/{id}` with htmx every two seconds until
the run ends.

**Existing actions move onto the system.** The Users bulk and row deletes and
the Logs bulk delete become `DeleteAction`s. `deleteBatch()`, the GET delete
route and the inline `confirm()` calls are removed. Removing routes is a
breaking change and is logged as one.

## Open questions

Answered on 2026-10-28 by taking each recommendation: the queue package is
suggested, modules may add actions to any list, runs show a toast only, and
the GET delete is removed as part of this work.

1. **Queue dependency.** Required, or suggested with the in-request fallback
   above. *Recommended: suggested.*
2. **Adding actions to other modules' lists.** Useful for add-on modules, but
   it makes each list name and the action API public. *Recommended: allow it.*
3. **An Action runs page under Tools.** A list of recent runs and their
   failures, like the Outbox in ADR 0001. *Recommended: ship the toast first,
   the page later.*
4. **The GET delete.** Fix it on its own ahead of this work, since it is a small
   security fix. *Recommended: yes.*

## Changes while building it

- **No `GET .../confirm` route.** Posting an action without `confirmed=1`
  answers with the confirmation page instead. A GET page would put the IDs,
  and the CSRF token of the form it came from, in the URL.
- **`<x-row-actions>` takes `id`, not `:entity`.** View Components only receive
  string attributes. The row menu filters by permission only; `allows()` is
  applied when the action runs, and rejected records are reported as skipped.
- **`fields()` is deferred** until ADR 0003's field classes exist; actions take
  no input yet.
- **Row actions submit one form in the layout** (`#bf-action-form`, rendered by
  `<x-confirm-dialog>`), since a row's menu sits inside the list's bulk form
  and forms can't nest.
- **A list names its record source** (`->records(new ModelSource(...))`, or any
  `RecordSource`), which the endpoint and the queue job use to load records.
- **The log page's single-file delete and "Delete all"** still post to
  `tools/delete-log`; only the Logs list's bulk delete moved.

## Consequences

- A new bulk or row action is one class and one registration line, with
  permission checks, CSRF, confirmation and no-JavaScript support included.
- Confirmations look the same everywhere, including `hx-confirm` ones.
- Installs that link to `users/{id}/delete` or post to `users/delete-batch`
  break, and need the changelog's breaking-change heading.
- Queued actions need a worker process; installs without one never get the
  background path.
- Installs must add each new action's permission to their published
  `AuthGroups` by hand, until #629 lets modules register their own.
- `Action`, `Confirm`, `ActionResult` and the list names become public API.
- `handle()` must be safe to run from a queue worker: no reliance on the
  request, session or current user beyond what `ActionContext` carries.

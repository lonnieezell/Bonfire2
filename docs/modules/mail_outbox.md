# Mail Outbox

The Outbox is a log of every email your site sends and every send that fails. Open **Mail Outbox** under **Tools** in
the sidebar. You need the `email.log` permission.

It records who each email went to and whether it was sent, never what it said. A magic link or password reset email
holds a live login token, so no message body is stored, in the log or anywhere else.

## What's logged

Each send makes one row:

| Column | What it holds |
| --- | --- |
| Date | When the send finished |
| Status | `sent`, or `failed` with the error |
| To, Cc, Bcc | The addresses, without display names |
| Subject | The subject line |
| From | The sender's address |
| Mailer, transport | See the note below |
| Mailable | The class that built the message, if it was a Mailable |
| Attachments | The file names, never the files |
| Message ID | The ID the provider returned, if it returned one |

A send a listener cancelled, or one where every recipient was on the suppression list, never reached a transport and
isn't logged. A transport that throws instead of returning a failure isn't logged either, because Postal doesn't report
those as failures.

An email sent through `service('email')`, or any other code that doesn't use a Mailable, is logged too. It has no
Mailable.

An error is saved with the secrets of your configured mailers replaced by `***`, the same way
[Test send](email.md#send-a-test-message) does it. An encoded secret can't be recognised, and values under 4 characters
are left alone.

!!! warning "Mailer is the default mailer"

    Postal doesn't tell its listeners which named mailer a message went through, so every row records the **default**
    mailer and its transport. A message sent through another mailer, with a Mailable's `transport()`, **Send to me** or
    **Test send**, is logged under the default mailer all the same. The mailer filter and column are only right for a
    site that sends everything through its default.

## Finding a send

Filter the list by any of these, and combine them:

| Filter | What it matches |
| --- | --- |
| Status | Sent or failed |
| Mailer | One of the mailers that appears in the log |
| Recipient | Text inside any To, Cc or Bcc address. `ann@` finds `ann@example.com`; `%` and `_` mean themselves. Addresses are stored JSON-encoded, so one with a non-ASCII character or a `/` can't be found by typing it |
| From date, To date | Sends on or between those days, both included |

The newest sends come first, a page at a time.

A row whose Mailable can be previewed links to its [preview](mail_previews.md), if you also have `email.preview`. A
Mailable that has since been renamed or deleted, or doesn't implement `Previewable`, shows its name without a link.

## On the dashboard

Two stat cards, **Mail sent, last 7 days** and **Mail failed, last 7 days**, count the Outbox's sends from the past
seven days. Add them from the dashboard's widget library, under **Email**. The failed card turns red while its count is
above zero, and each card's detail link opens the Outbox filtered to that status.

You need `email.log` to see the cards, and they're missing from the library while **Days to keep** is `0`. The counts
are only queried while the dashboard shows a card.

## Keeping it small

**Settings → Email** has **Days to keep**, which is `Email.logRetentionDays`. It starts at 30. Rows older than that are
deleted, and:

- `0` turns the Outbox off. Nothing new is logged, and pruning then deletes every row that's left.
- A write prunes the log about one time in a hundred, so a site without cron stays bounded.
- `php spark email:prune` prunes on demand and prints how many rows it deleted. Run it from cron, or from your
  [scheduled tasks](https://github.com/codeigniter4/tasks), to keep the log trimmed on a quiet site too.

Logging never fails a send. If the `email_log` table is missing, or the database errors, the email still goes out and
the problem is written to the application log. Run `php spark migrate --all` after updating to create the table.

## Giving access

`email.log` is granted to the `superadmin` group (through `email.*`). It isn't granted to `admin`, because the Outbox
shows the addresses of everyone you've emailed. Anyone without it sees no menu item and gets a 404 from the page.

An install has its own copy of `app/Config/AuthGroups.php`, so after updating Bonfire the screen stays hidden until you
grant the permission:

```console
php spark bonfire:permissions
```

This adds the grant above once. You can also grant `email.log` to any group from **Settings → Groups**.

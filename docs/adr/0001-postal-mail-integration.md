# ADR 0001: Send all mail through Postal, configured in code

- **Status:** Accepted; partly superseded by [ADR 0008](0008-zero-touch-postal-upgrade.md)
- **Date:** 2026-10-27

## Context

Bonfire's Email module stored a flat CodeIgniter transport config (protocol, SMTP
host, port, user, password) in the Settings database, and sent mail through
Shield's `emailer()` helper. We want Bonfire to use Postal (`myth/postal`):
named mailers, more transports (SES, Resend, failover, DKIM), Mailable classes
with previews, and send lifecycle events.

Bonfire is heading towards a product developers might pay for, in the mould of
Laravel Nova. Its buyer is the developer; their clients use the admin. Paid
admin panels in that space (Nova, Filament, Statamic) leave mail transport
config to code and environment, and sell visibility instead.

Installing Postal breaks Shield's `emailer()`: it is declared to return
`CodeIgniter\Email\Email`, while Postal's `service('email')` returns
`Myth\Postal\LegacyEmailAdapter`, which does not extend it. Every activation,
2FA and magic-link email would throw a `TypeError`.

## Decision

**Postal is a hard dependency.** Bonfire sends all of its own mail through
Postal Mailables. The release is marked as a breaking change.

**Bonfire stops calling `emailer()`.** The activation, two-factor and magic-link
emails become Mailables in `Bonfire\Auth\Mails` that implement `Previewable`.
Bonfire's `MagicLinkController` overrides `loginAction()` to send its Mailable.
For now the Mailables render the views named in `Auth.views`, so installs that
customised those views keep them. Making `LegacyEmailAdapter` extend
CodeIgniter's `Email` is filed against Postal and does not block this work.

**Transport config lives in code.** Mailers, credentials, failover and DKIM live
in `app/Config/Mailer.php` and `.env`; Bonfire never stores them. Settings →
Email (`admin.settings`) shows the defined mailers read-only, masking secrets
on the server by key name (`pass|secret|key|token|credential|auth`, nested
arrays included). It keeps the editable From name and address and the Outbox
retention, and sends a transport test message through a chosen mailer.
`spark bonfire:email-env` prints an install's old database settings as `.env`
lines and a `Mailer.php` snippet; `--purge` then deletes them from the database.

**The From setting is the site-wide default.** An `email.composing` listener
fills in `from` from `setting('Email.fromEmail')` / `setting('Email.fromName')`
when a message has none. Bonfire's own Mailables rely on it and do not call
`from()`.

**Previews live in the admin.** Tools → Mail → Previews (`email.preview`)
renders Mailables in the Admin theme, in production too, because admin login
and the permission protect it instead of Postal's non-production lock. It finds
`Previewable` Mailables in the `Mails/` folder of every core and app module plus
Postal's `mailableNamespaces`, grouped by module. HTML is shown in an
`<iframe sandbox srcdoc>` with no scripts allowed. "Send to me" mails a preview
to the signed-in admin's own address only. `previewInstance()` must not
persist anything.

**The Outbox logs metadata, never bodies.** Listeners on `email.sent` and
`email.failed` write one row per send to `email_log`: mailer, transport,
Mailable class, subject, addresses, attachment names, status, provider message
ID and error. Bodies are not stored, because magic-link and password-reset
emails carry live login tokens. Tools → Mail → Outbox needs `email.log`. Rows
older than `Email.logRetentionDays` (default 30, `0` turns logging off) are
removed by `spark email:prune` and by a lazy prune on about one write in a
hundred. A logging failure never fails a send. A dashboard stats widget shows
the last seven days' sent and failed counts.

**Postal changes ship first.** Postal gains a public `MailablePreviewer` that
takes a namespace => path map, and stamps the originating class on
`Email::$mailable` when a Mailable renders. Bonfire requires the Postal release
that has both.

## Consequences

- Admins without code access can no longer change SMTP credentials from the
  browser; an upgrade means moving them to `.env`.
- Installs publish their own `AuthGroups`, so they must add `email.preview` and
  `email.log` by hand. Until they do, the new screens stay hidden.
- A custom transport whose secret uses a key name outside the mask pattern is
  shown in full on the settings page.
- Legacy `service('email')` sends take their From from `Config\Email` before the
  `composing` listener runs, so a `fromEmail` set in that file wins over the
  Bonfire setting.
- The magic-link `loginAction()` override copies Shield logic that has to be
  kept in step with Shield releases.
- A branded Markdown mail layout for the auth emails is a separate follow-up.

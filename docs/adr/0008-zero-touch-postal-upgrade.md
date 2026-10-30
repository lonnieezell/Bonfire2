# ADR 0008: Existing installs keep sending mail after the Postal upgrade

- **Status:** Accepted
- **Date:** 2026-10-30
- **Supersedes:** parts of ADR 0001 (see below)

## Context

ADR 0001 moves Bonfire's mail to Postal, with transport config in
`app/Config/Mailer.php` and `.env`. As written, that is a cliff for existing
installs. Postal's stock `Config\Mailer` has `$default = 'null'`, so an install
that runs `composer update` without creating `Mailer.php` would discard every
activation and 2FA email without an error, and email 2FA would lock users out.
Bonfire can't run code during `composer update`: `notify:breaking-changes` runs
only if the install added it to `post-update-cmd`, and it can only warn.

Today an install's transport settings come through `setting('Email.*')`: a
Settings database row, else `app/Config/Email.php`, else its `.env` `email.*`
overrides. Shield's `emailer()` reads them that way. Framework defaults give
every install `protocol = 'mail'`, so even an install that never touched
Settings → Email sends through PHP `mail()`.

Two of ADR 0001's premises have changed:

- lonnieezell/postal#52 is fixed (`v1.0.0-beta.7`): `LegacyEmailAdapter` extends
  CodeIgniter's `Email`, so Shield's `emailer()` no longer throws a `TypeError`.
- `LegacyEmailAdapter` builds its transport from the flat `Config\Email` keys
  and ignores `Config\Mailer`. Once an app configures Postal, anything still
  using `service('email')` or `emailer()` would bypass the mailer it set up.

## Decision

**Mail keeps working after the upgrade.** While `config('Mailer')` is Postal's
own `Myth\Postal\Config\Mailer`, meaning the app hasn't published one, Bonfire
makes a `legacy` mailer the default, built from `setting('Email.*')`. Once the
app has its own Mailer config, Bonfire steps aside completely, even if that
config's default is `null`.

**The bridge reads settings lazily.** A `pre_system` listener only registers a
`bonfire-legacy` transport and the `legacy` mailer, without touching the
database. `LegacySettingsTransport` reads the settings at send time and hands
the message to the smtp, sendmail or mail transport they describe.

**One mapping, in Postal.** Postal exposes the flat-key → mailer mapping its
legacy adapter uses (lonnieezell/postal#60). The bridge and
`spark bonfire:email-env` both use it, so the printed `Mailer.php` behaves
exactly like the bridge did.

**Legacy sends follow the configured mailer.** Once the app has its own Mailer
config, `LegacyEmailAdapter` sends through the default mailer and ignores flat
transport keys (lonnieezell/postal#61). Mailables, `service('email')` and
Shield's `emailer()` then all obey `Mailer.php`.

**The magic link stays on Shield.** Activation and 2FA become Bonfire Mailables,
because Bonfire already owns those actions. Bonfire does not override Shield's
`MagicLinkController::loginAction()`, so there is no copied Shield logic to keep
in step. The magic link reaches the configured mailer through the legacy
adapter and appears in the Outbox without a Mailable class, but not in Previews.

**Purging waits for the move.** `spark bonfire:email-env` labels each value's
source (database, `Config\Email`, `.env`). `--purge` refuses while the bridge is
active, even with `--force`, because purging first would quietly drop the
bridge back to PHP `mail()`.

**The bridge is deprecated from day one** and removed in 2.0. The SMTP fields
leave Settings → Email immediately; the page shows the `legacy` mailer
read-only under a deprecation warning, and the bridge logs a deprecation notice.

**This ships before Bonfire 1.0,** on Postal 1.0 (#51, #53, #60 and #61).

ADR 0001's sections "Bonfire stops calling `emailer()`" (its `TypeError`
rationale and the magic-link override) and "Postal changes ship first" (now
extended by #60 and #61) are superseded by this record. The rest of 0001 stands.

## Consequences

- An upgrade changes no delivery behaviour. The breaking change is that admins
  can no longer edit SMTP settings in the browser.
- Until 2.0, Settings → Email shows two ways of configuring mail, the bridged
  `legacy` mailer and `Mailer.php`, and the docs must explain both.
- Plaintext SMTP passwords can stay in the settings table until someone runs
  `--purge`. The dashboard warns while they do.
- Publishing a `Mailer.php` turns the bridge off immediately, so a developer who
  publishes Postal's stock config without editing it gets the `null` mailer.
  The settings page warns when the default is `null`.
- An app with its own Mailer config can no longer pick a transport per message
  through `setProtocol()` or flat keys on `service('email')`.
- Removing the bridge in 2.0 is a breaking change, and needs its own changelog
  entry.

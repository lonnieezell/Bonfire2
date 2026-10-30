# ADR 0006: Erasing a user anonymises the account and keeps login rows

- **Status:** Accepted
- **Date:** 2026-10-30

## Context

The GDPR epic (#746) gives admins a way to erase a user's personal data
(Art. 17) through `service('personalData')`, which runs every registered
`PersonalDataProvider`.

Sites built on Bonfire add their own tables that point at `users.id`: posts,
orders, comments. Bonfire doesn't know about them. Hard-deleting the user row
either cascades into that data or fails on foreign key constraints.

Shield keeps a security audit trail in `auth_logins` and `auth_token_logins`:
IP address, user agent, the `identifier` used (usually the email) and the
`user_id`, for failed attempts too. Its migration says not to delete the
`user_id` or `identifier` when a user is deleted, for security audits.

## Decision

**Erasure anonymises; it doesn't delete the user row.** The `users` provider:

- overwrites `username`, `first_name` and `last_name` with an anonymous value
  such as `deleted-user-{id}`;
- deletes the avatar, user meta, Shield identities (email, password hashes,
  tokens) and remember tokens;
- deactivates and soft-deletes the account, and sets `erased_at`.

The row can't sign in and identifies no one, and its ID still satisfies every
foreign key.

**Login rows are kept, minus the email.** Their `identifier` is overwritten with
the anonymous value. `user_id`, IP address and user agent stay, so events can
still be linked for a security audit, until retention prunes them (#751).

**Each provider decides for its own data.** A module may delete or anonymise
in `erase()`. Providers run in one DB transaction with the `users` provider
last, and files are deleted only after commit.

**Erased users can't come back.** The Recycler doesn't list or restore users
with `erased_at` set.

Hard deletion and a per-install `$eraseMode` setting were rejected: the first
breaks sites' data, and the second makes every install answer a question this
one answers.

## Consequences

- Sites' content keeps working after an erasure, attributed to an anonymous
  user.
- Login IP addresses outlive the account for up to the login retention period
  (90 days by default). This relies on legitimate interest in security and on
  retention actually running, which is why System Info warns when pruning
  stops.
- If retention is set to `null` for logins, IPs are kept indefinitely. The docs
  say so.
- `erased_at` is a new column on `users`, and the Recycler's `users` resource
  needs a way to leave those rows out.
- A module that stores personal data and doesn't register a provider isn't
  erased. The provider guide (#752) makes that the module author's job.

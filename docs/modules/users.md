# Users

The Users module is where you add, edit, ban and delete the people who can log in to your site. Open it from
**Users** under **Content** in the sidebar. You need the `users.list` permission to see it.

The rules for registration, logins, passwords and avatars live on a separate screen,
[Settings → Users](#settings-users).

## The user list

The list shows each user's avatar, name and email, username, groups, status and when they were last active. A user's
status is one of:

- **Active**: can log in.
- **Pending**: has registered but hasn't confirmed their email address yet.
- **Banned**: can't log in.

To narrow the list down:

- type in the search box to match names, usernames and email addresses;
- click a group chip to show only that group's members. Each chip shows how many users are in that group;
- open the filter panel to filter by group, by whether a user is active or banned, or by when they were last
  active.

The list shows 15 users per page by default. There's no field for this in the admin; a developer sets it with
`Site::$perPage`, see the [Configuration Reference](../intro/configuration.md#site).

## Add a user

You need the `users.create` permission.

1. Click **New User** above the list.
2. Fill in the email address and username. First and last name are optional.
3. Pick the user's groups, and upload an avatar if you like.
4. Click **Save User**.

A user you add is active straight away. They get a random password, so either
[set one for them](#change-a-users-password) or ask them to use the "forgot password" link on the login page.

!!! note
    Email addresses must be unique, and that includes users in the Recycler. If an address is "already in use"
    but you can't find the user, look in the Recycler under **Tools**.

## Edit a user

Click a user's name in the list. Their page has three tabs:

- **Details**: profile, access and groups.
- **Permissions**: permissions given to this user on top of their groups'. You need `users.edit` to see this tab.
- **Security**: change the password and see recent logins.

Other modules can add tabs of their own.

You need `users.edit` to edit other users. Users with the `me.edit` permission can edit their own details, and those
with `me.security` can change their own password.

### Upload or remove an avatar

On the **Details** tab, choose an image under the avatar, then click **Save User**. A new image replaces the old
one. To remove an uploaded image, click **Delete uploaded image** and confirm.

Without an uploaded image, a user's avatar shows their initials, or their Gravatar if
[Gravatar is turned on](#avatars).

### Activate a user

A **Pending** user hasn't confirmed their email address. To let them in without it, turn on **User is activated**
and click **Save User**. Once a user is active, you can't switch them back to pending.

### Ban a user

1. Turn on **User is banned**.
2. Optionally, enter a reason. The user sees it when they try to log in.
3. Click **Save User**.

To lift the ban, turn the switch off and save. You can't ban yourself, and only users with `users.manage-admins` can
ban members of the Admin or Super Admin groups.

### Change a user's groups

Tick the groups the user belongs to, then click **Save User**. Each group card shows how many permissions the group
grants.

Only users with `users.manage-admins` can add someone to the Admin or Super Admin groups, or take them out. For
everyone else those groups are greyed out, and saving leaves them as they were.

### Give a user extra permissions

A user gets every permission of their groups. The **Permissions** tab adds permissions for this one user:

1. Open the **Permissions** tab.
2. Turn on the permissions to add. A permission the user already has through a group shows a lock and the group's
   name, like *via admin*. To take it away, change the user's groups.
3. Click **Save Permissions**.

Only users with `users.manage-admins` can give someone a new `users.*` permission.

### Change a user's password

1. Open the **Security** tab.
2. Enter the new password twice. The meter next to the field shows how strong it is.
3. Click **Update Password**.

The password has to follow the rules under [Passwords](#passwords).

The **Recent Logins** table below shows the user's last 20 login attempts: when, from which IP address and browser,
and whether they succeeded.

## Delete users

You need the `users.delete` permission.

1. Tick the users in the list, or open the ⋯ menu on a user's row.
2. Choose **Delete** and confirm.

You can't select yourself, and only users with `users.manage-admins` can delete users who manage admins.

Deleted users go to the Recycler under **Tools**, where you can restore them or delete them for good.

## Export a user's data

You need the `users.export` permission. Click **Export data** at the top of a user's edit screen to download everything
the site holds about them as a JSON file. Only users with `users.manage-admins` can export users who manage admins.

## Erase users' personal data

You need the `users.erase` permission. Tick the users in the list, or open the ⋯ menu on a user's row, choose **Erase
personal data**, and type the number of users to confirm. Their accounts are anonymised and closed for good, and
can't be restored from the Recycler.

For both, see [Handle a Data Export or Erasure Request](../building_sites/data_requests.md).

## Settings → Users

Open **Users** under **Settings** in the sidebar. You need the `users.settings` permission. Click **Save Settings**
when you're done.

### Registration

| Setting | What it does |
| --- | --- |
| Allow registration | Lets people sign up on the site themselves. When off, only an admin can add users. |
| Force email verification | Emails new users a code to confirm their address. Until they do, they're **Pending**. |
| Default user group | The group people join when they sign up. |

### Login

| Setting | What it does |
| --- | --- |
| Remember users | Offers "remember me" at login, so users don't have to log in on every visit. |
| Remember for | How long "remember me" lasts, from 1 hour to 1 year. Shown when **Remember users** is on. |
| Force 2FA after login | Emails users a code to confirm each login. |

### Passwords

| Setting | What it does |
| --- | --- |
| Minimum length | The fewest characters a password can have. At least 8; 10 is recommended. |
| Composition Validator | Checks the password's length requirements. |
| Nothing Personal Validator | Rejects passwords too similar to the user's email, username or other personal details. |
| Dictionary Validator | Rejects about 600,000 known leaked passwords, using a list on your server. |
| Pwned Validator | Rejects leaked passwords by asking haveibeenpwned.com. |

The Dictionary and Pwned validators do the same job, so you only need one. Pwned has the bigger list but sends a
partial hash of each new password to an outside service. With no validator on, a password only needs the minimum
length.

### Avatars

| Setting | What it does |
| --- | --- |
| Initials from | What avatar initials are made from when there's no image: **Full Name** (first and last initial, or the first two letters of the username if there's no first name) or **Username** (its first two letters). |
| Use Gravatar | Shows a user's [Gravatar](https://gravatar.com) when they haven't uploaded an avatar. |
| Gravatar default style | The picture Gravatar shows for addresses without one, such as *mystery person* or *identicon*. Shown when **Use Gravatar** is on. |
| Downsize on upload | Shrinks uploaded avatars that are bigger than **Max dimensions**. |
| Max dimensions | The largest width or height of an uploaded avatar, in pixels. Shown when **Downsize on upload** is on. It can't be less than `Users::$avatarResizeFloor`, 32 by default. |

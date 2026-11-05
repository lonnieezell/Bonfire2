# Getting started

This walks you from a fresh Bonfire install to a published blog post on your front end. It takes six steps and about ten
minutes. It assumes you have [installed Bonfire](../intro/installation.md) and that the site runs.

You'll make these files:

```
app/Config/Content.php                                one line changed
content/collections/blog.yaml                         the collection
content/collections/blog/2026-10-27.hello-world.md    the post
resources/blueprints/collections/blog/post.yaml       its admin form
app/Views/blog/show.php                               the template
```

## 1. Check that Content is on

Content is a module of Bonfire, so there's nothing to switch on. `php spark bf:install` already:

- copied `app/Config/Content.php` into your app,
- ran the migrations that create the index tables, and
- granted the `content.*` [permissions](admin.md#permissions) to `superadmin` and `admin`.

If you added Bonfire's Content to a site that was installed before it existed, run these two commands once:

```bash
php spark migrate --all
php spark bonfire:permissions
```

Log in to the admin. Content's sidebar item appears once there is a collection to show, which is the next step.

## 2. Create a collection

A collection is a group of entries. Make `content/collections/blog.yaml` in your project root, next to `app/`:

```yaml
title: Blog
dated: true
blueprints:
  - post
route: /blog/{slug}
template: blog/show
```

- `dated: true` puts a date in each file's name and sorts the newest first.
- `blueprints` names the form that the admin uses for the entries.
- `route` and `template` are what serve the entries on your site. `{slug}` is replaced with the entry's slug. They
  have no effect until you turn on routing in step 5.

All the keys are in [Collections](collections.md#collection-config).

## 3. Create a blueprint

A blueprint lists the fields of the admin form. Make `resources/blueprints/collections/blog/post.yaml`:

```yaml
title: Post
fields:
  - handle: title
    type: text
    validate: required|max_length[255]
  - handle: slug
    type: slug
  - handle: body
    type: markdown
    validate: required
```

The blueprint lives in `resources/blueprints/collections/<collection>/`, not in `content/`. See
[Blueprints](blueprints.md) for the rest of the format and [Field types](field_types.md) for what a field can be.

## 4. Write a post

You can write a post in the admin, or as a file.

**In the admin.** Open **Content → Blog** in the sidebar and choose **New entry**. Fill in the title and body, set the
status to **Published**, and save. See [Editing entries](admin.md).

**As a file.** Make `content/collections/blog/2026-10-27.hello-world.md`:

```markdown
---
id: 0f8b4e2c-6a1d-4c3e-9b7a-2d5f1e8c4a90
title: Hello world
---
This is my *first* post.
```

The `id` must be a UUID that no other entry has, so make a new one for each file, such as with `uuidgen`. The date in
the filename is when the post goes live, so use today's date or an earlier one, or the post is a 404 until that day.
Entries without a `status` are published. See [Entries](collections.md#entries).

Then index the file. The admin does this for the entries it saves, but not for files you wrote by hand:

```bash
php spark content:reindex
```

It prints how many entries it added. If it lists a file with a reason instead, fix that file and run it again. See
[The index and deploys](the_index.md).

## 5. Turn on routing

Serving entries is opt-in. In `app/Config/Content.php`, set:

```php
public bool $autoRoute = true;
```

This adds a fallback route that Bonfire places after all of yours, so a route of your own at the same address wins. See
[Routing](routing.md#turning-it-on) for what it does to 404s and auto-routed controllers.

## 6. Write a template

The collection's `template: blog/show` is a view. Make `app/Views/blog/show.php`:

```php
<?= $this->extend('master') ?>

<?= $this->section('main') ?>
    <h1><?= esc($entry->title) ?></h1>

    <?= $entry->content() ?>
<?= $this->endSection() ?>
```

It gets `$entry` and `$collection`. `$entry->content()` renders the Markdown body as HTML. See
[Templates](routing.md#templates) for where templates are looked up, and [Entries](query_api.md#entries) for everything
an entry offers.

!!! tip

    Next time, `php spark content:scaffold blog` can write a fuller show view for you, built from the blueprint's
    fields, along with a page that lists the posts. See [Scaffolding](scaffolding.md).

## See the post

Open `/blog/hello-world` on your site, such as `http://localhost:8080/blog/hello-world`. You should see the title and
the post.

If you get a 404 instead, check these in order:

1. `$autoRoute` is `true` in `app/Config/Content.php`, the one in your `app` folder.
2. The post is `published`, and its date or `published_at` is not in the future.
3. You ran `php spark content:reindex` after the last change to a file or to the collection's `route`. The URI of an
   entry is stored in the index. In `development` the index
   [catches up with your files by itself](the_index.md#in-development), but in `production` it doesn't.
4. The reindex didn't list the file as a problem. Two entries can't share a URI, and `route` must contain `{slug}`.

## Where next

- To list your posts, run [`php spark content:scaffold blog`](scaffolding.md), or follow the
  [listing recipe](query_api.md#listing-a-collection).
- To tag posts, see [Taxonomies](taxonomies.md). To keep pages in a tree, see [Pages and structures](structure.md). To
  build a site menu, see [Navigation menus](navigation.md).
- Before you deploy, read the [Constraints and known gaps](constraints.md), and add `php spark content:reindex` to your
  deploy steps. See [When to reindex](the_index.md#when-to-reindex).

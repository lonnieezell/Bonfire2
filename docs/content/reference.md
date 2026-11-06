# Configuration and commands

## Configuration

Content's settings are in `app/Config/Content.php`, which `php spark bf:install` copies into your app. It extends
`Bonfire\Content\Config\Content`.

| Property | Default | What it does |
|---|---|---|
| `$contentPath` | `ROOTPATH . 'content/'` | The content root, which holds collections, entries, taxonomies and the rest. See [File layout](index.md#file-layout). |
| `$blueprintPath` | `ROOTPATH . 'resources/blueprints/'` | Where blueprints live. See [Blueprints](blueprints.md). |
| `$autoRoute` | `false` | Serves entries at their URIs through the fallback route. See [Routing](routing.md#turning-it-on). |
| `$autoSeo` | `false` | Adds an entry's SEO tags to the pages the fallback route renders from a `template`. See [SEO](seo.md#doing-it-automatically). |
| `$sitemap` | `false` | Serves the sitemap at `/sitemap.xml`. See [Sitemap](feeds_and_sitemaps.md#the-built-in-sitemap-route). |
| `$feedRoute` | `false` | Serves each collection's feed at `/feed/{collection}`. See [Feeds](feeds_and_sitemaps.md#the-built-in-feed-route). |

Your own routes win over every route these settings add.

Field types of your own aren't a Content setting: they go in `$types` in `app/Config/Fields.php`. See
[Custom field types](custom_field_types.md#register-it).

A collection's own settings, such as `route`, `template` and `cache`, are in its config file. See
[Collection config](collections.md#collection-config).

## Commands

### content:reindex

```bash
php spark content:reindex [--fresh]
```

Updates the index to match the content files. Run it on every deploy.

| Option | What it does |
|---|---|
| `--fresh` | Empties the index and rebuilds it, in one transaction. |

It exits with an error code if the content root has no `collections` folder or the index can't be written. See
[The index and deploys](the_index.md).

### content:scaffold

```bash
php spark content:scaffold <collection> [--controller]
```

Writes starting index and show views for a collection into `app/Views/<collection>/`, built from its blueprints. It never
overwrites a file.

| Argument or option | What it does |
|---|---|
| `collection` | The handle of the collection. |
| `--controller` | Also writes a controller for the collection to hand off to. |

See [Scaffolding](scaffolding.md).

### Related commands

| Command | Why Content needs it |
|---|---|
| `php spark migrate --all` | Creates the index tables. `bf:install` runs it. |
| `php spark bonfire:permissions` | Grants the `content.*` permissions to `superadmin` and `admin`. See [Permissions](admin.md#permissions). |
| `php spark key:generate` | Sets the encryption key that signs [draft preview links](admin.md#previewing-a-draft). |

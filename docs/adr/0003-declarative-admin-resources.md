# ADR 0003: Declarative admin resources, opt-in beside hand-written modules

- **Status:** Proposed
- **Date:** 2026-10-28

## Context

ADR 0001 names Laravel Nova as the mould for Bonfire as a product. Nova and its
peers (Filament, Backpack, Symfony's EasyAdmin, Rails' Avo) are resource
builders: one class per model describes its fields, filters and actions, and the
package renders the list, form, search and bulk actions. Bonfire has no such
layer. Its admin screens are hand-written controllers and views, helped by the
`<x-…>` view components and `CrudGenerator`, which writes code the developer
then maintains. `UserController` shows the cost: about 200 lines of list, form,
save and permission plumbing for one model.

Bonfire already has most of the parts a resource needs, each wired by hand per
module: the `Filterable` trait and `Filters` cell, `SearchProviderInterface`,
`service('recycler')->register()`, sidebar `MenuItem`s, `ResourceTabs`, and the
`<x-selection-bar>`, `<x-status>`, `<x-switch>` and `<x-setting-row>` components.

`Bonfire::boot()` runs `initAdmin()` from the `Admin` filter, after routing. A
module cannot add routes from `Module.php`.

## Decision

**Resources are opt-in and additive.** A module may describe a model with a
resource class instead of writing controllers and views. Hand-written modules,
`Users` included, keep working unchanged, and `CrudGenerator` stays for
developers who want plain controllers.

**A resource is one class.** `Bonfire\Resources\Resource` names the model, URL
slug and permission prefix, and lists its fields, filters, actions and search
columns:

```php
class PostResource extends Resource
{
    public string $model      = PostModel::class;
    public string $slug       = 'posts';
    public string $permission = 'posts';
    public array  $search     = ['title', 'body'];

    public function fields(): array
    {
        return [
            Text::make('title')->rules('required|max_length[200]')->sortable()->link(),
            BelongsTo::make('author_id', UserModel::class, 'username')->label('Author'),
            Select::make('status', ['draft' => 'Draft', 'published' => 'Published'])->status(),
            Boolean::make('featured'),
            Textarea::make('body')->hideFromIndex(),
        ];
    }

    public function filters(): array
    {
        return ['status' => ['title' => 'Status', 'options' => ['draft' => 'Draft', 'published' => 'Published']]];
    }

    public function actions(): array
    {
        return [new PublishAction(), new DeleteAction()];
    }
}
```

**Registering wires the admin.** `service('resources')->register(PostResource::class, collection: 'content', icon: '…')`
in `initAdmin()` adds the sidebar item, registers the model with the Recycler
using `$search` as its columns, and adds a search provider built from `$search`.

**Routes are declared, not discovered.** Because routing runs before
`initAdmin()`, the module's `Config/Routes.php` calls
`resource_routes($routes, PostResource::class)`. It expands to the route table
modules write by hand today: list (GET and POST, for htmx), new, edit, save,
delete, delete-batch and `actions/(:segment)`. A `controller:` option points
the routes at a subclass of the generic controller.

**One generic controller.** `ResourceController` extends `AdminController` and
implements list, create, edit, save, delete, deleteBatch and action for any
resource. It checks `{permission}.list|create|edit|delete`; a resource
overrides `can($verb, $entity = null)` for per-record rules, the way
`UserAccess` works. `query(Model $model): Model` lets a resource add joins and
eager loads before the list query runs.

**Fields render through existing components.** Each field has an index-cell
view and a form-input view built from the Admin theme's components. v1 ships
seven: `Text`, `Textarea`, `Number`, `Select`, `Boolean`, `DateTime`,
`BelongsTo`. A custom field is a subclass with its own views.

**Filters and bulk actions reuse what exists.** `filters()` returns today's
`$filters` shape and goes through the `Filters` cell and `Filterable::filter()`.
Actions appear in `<x-selection-bar>`; `Action::handle(array $ids)` receives the
selected IDs. `DeleteAction` soft-deletes, so records land in the Recycler.

**Every layer can be overridden without leaving resources.** In order of
reach: a `list.php` or `form.php` beside the resource replaces the package
view; a `ResourceController` subclass replaces one method; a `ResourceTab`
adds a hand-written tab to the edit page; a hand-written module replaces the
resource entirely.

**Resource views stay in the package.** They live in `src/Resources/Views` and
are not published into installs' Admin theme, so changing them is not a
breaking change. Installs override them per resource instead.

**A generator writes resources.** `spark bf:resource Post --model=PostModel`
writes the resource class, inferring field types from the table's columns, and
writes `Module.php` and `Config/Routes.php` when the module has none.

**Out of scope for v1:** has-many relation tables on edit pages, file and image
fields, rich text, queued actions, metrics, and a field plugin registry.

## Open questions

These need an answer before the status moves to Accepted. Each has a
recommendation, not a decision.

1. **Field API style.** Fluent builders (`Text::make()->rules()`, as above) or
   config arrays like `$filters`. Fluent is easier to discover and gives IDE
   completion; arrays match Bonfire's current idiom. *Recommended: fluent.*
2. **Where validation rules live.** On the fields (as above), or on the
   model's `$validationRules` with the resource describing only display. The
   model keeps one source of rules; fields allow per-screen rules.
   *Recommended: on the model by default, with field `rules()` added on top.*
3. **First resource to build.** Converting `Users` is the hardest test
   (avatars, meta info, Shield identities) and risky as a first target.
   *Recommended: a new demo module first, then `Users` once the API holds.*
4. **Pricing tier.** ADR 0001 positions Bonfire as a product developers might
   pay for. Whether resources are part of the free core or a paid tier is
   undecided.

## Consequences

- A CRUD admin screen becomes one class plus two registration lines, instead of
  a controller, routes and three views.
- Resource screens all look and behave alike; a screen that departs from the
  pattern needs view or controller overrides, or a hand-written module.
- Two ways to build an admin module coexist, and the docs must say when to use
  each.
- Routes are declared apart from the resource, so a module can register a
  resource and forget its routes. The generator writes both to make that less
  likely.
- Installs must add the `{permission}.*` keys to their published `AuthGroups`
  by hand for each resource, as with any new module.
- Field and action classes become public API; renaming their methods later is a
  breaking change.

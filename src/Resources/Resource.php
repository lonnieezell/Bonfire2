<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Resources;

use Bonfire\Core\Like;
use Bonfire\Fields\Field;
use Bonfire\Fields\FieldException;
use Bonfire\Fields\FieldTypes;
use Bonfire\Fields\Form;
use Bonfire\Fields\Section;
use Bonfire\Fields\Types\TextareaField;
use Bonfire\Resources\Fields\Field as FieldBuilder;
use Bonfire\Resources\Fields\Section as SectionBuilder;
use CodeIgniter\Model;

/**
 * Describes a model for the admin: where it lives, who may use it and what the list shows.
 * Extend it, then register the class with `service('resources')->register()` in a module's
 * initAdmin() and declare its routes with `resource_routes()` in the module's Config/Routes.php.
 */
abstract class Resource
{
    /**
     * The model that holds the records.
     *
     * @var class-string<Model>
     */
    public string $model;

    /**
     * The URL segment after the admin area, and the prefix of the route names.
     */
    public string $slug;

    /**
     * The prefix of the permissions that guard the pages, such as `categories` for `categories.list`.
     */
    public string $permission;

    /**
     * The pages the resource has: `list`, `create` and `edit`. The routes and buttons of a page
     * that isn't named here are left out.
     *
     * @var list<string>
     */
    public array $pages = ['list'];

    /**
     * The columns of the model's table that the list's search box and the admin's global search look in.
     * A record matches when any of them contains the search. Leave it empty for a resource without search.
     *
     * @var list<string>
     */
    public array $search = [];

    /**
     * The name in the sidebar and above the list.
     */
    public function title(): string
    {
        return ucfirst(str_replace(['-', '_'], ' ', $this->slug));
    }

    /**
     * What a record has: fields, and sections that group fields. Write a field with the builders
     * in `Bonfire\Resources\Fields` or as a `Bonfire\Fields\Field`. A field outside a section goes in a group
     * with no title.
     *
     * @return list<Field|FieldBuilder|Section|SectionBuilder>
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * The fields of a record, whether or not they are in sections.
     *
     * @return list<Field>
     */
    public function formFields(): array
    {
        return $this->layout('')[0];
    }

    /**
     * The form of a record. `{id}` in a field's rules is the ID of the record being edited.
     *
     * @param string $id the ID of the record, or '' for a new one
     *
     * @throws FieldException when a field's options can't be used
     */
    public function form(string $id = ''): Form
    {
        [$fields, $sections] = $this->layout($id);

        foreach ($fields as $field) {
            FieldTypes::get($field->type)->check($field);
        }

        return new Form($fields, [], $sections);
    }

    /**
     * The columns of the list: the fields, except those that hold long text, which don't fit a table cell.
     * A column that is a column of the model's table can be sorted. Return `Column` objects to show a relation's
     * column or a computed value.
     *
     * @return list<Column|Field>
     */
    public function columns(): array
    {
        return array_values(array_filter(
            $this->formFields(),
            static fn (Field $field): bool => ! FieldTypes::get($field->type) instanceof TextareaField,
        ));
    }

    /**
     * Whether the signed-in user may do `list`, `create`, `edit` or `delete` to a record, or to any record when
     * `$entity` is null. By default that is having `{permission}.{verb}`. Override it for rules that depend on the record.
     */
    public function can(string $verb, mixed $entity = null): bool
    {
        return (bool) auth()->user()?->can($this->permission . '.' . $verb);
    }

    /**
     * Changes the values a save is about to give the model, which are the fields' stored values by handle.
     * Add values, or drop them. Runs on every post, even one that has errors, so that the model's rules check
     * the values it would save.
     *
     * @param array<string, mixed> $data   the values, as `$model->save()` will get them
     * @param mixed                $record the record being edited, or null for a new one
     *
     * @return array<string, mixed>
     */
    public function beforeSave(array $data, mixed $record): array
    {
        return $data;
    }

    /**
     * Runs after the model has saved a record.
     *
     * @param mixed $record the saved record, read back from the model: an entity, or an array when the model returns arrays
     */
    public function afterSave(mixed $record): void
    {
    }

    /**
     * The filters of the list, in the shape of a model's `$filters` (see Filterable): the key is a column of
     * the model's table, and each is an array with a `title`, the `options` and, optionally, a `type` of `radio`.
     * A string for `options` names a method of the resource that returns them. A record matches a filter when
     * its column holds any of the chosen options.
     *
     * @return array<string, array{title: string, options: array<int|string, string>|string, type?: string}>
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * The filters, with the options that a method computes filled in.
     *
     * @return array<string, array{title: string, options: array<int|string, string>, type?: string}>
     */
    public function getFilters(): array
    {
        $filters = $this->filters();

        foreach ($filters as &$filter) {
            if (is_string($filter['options'])) {
                $filter['options'] = $this->{$filter['options']}();
            }
        }

        return $filters;
    }

    /**
     * Prepares the model before the list adds its sorting and paging, for joins and eager loads.
     */
    public function query(Model $model): Model
    {
        return $model;
    }

    /**
     * Narrows the model to the records where any `$search` column contains the term.
     */
    public function applySearch(Model $model, string $term): Model
    {
        $term = trim($term);

        if ($term === '' || $this->search === []) {
            return $model;
        }

        $needle = Like::escape($term, $model->db);

        $model->groupStart();

        foreach ($this->search as $column) {
            // like() quotes the column without adding the table prefix, so this does it.
            $model->orLike($model->db->protectIdentifiers($this->qualify($model, $column)), $needle, 'both', true, true);
        }

        return $model->groupEnd();
    }

    /**
     * Narrows the model to the records that match the chosen options of each filter. Only the columns that
     * `filters()` names count, and a filter with nothing chosen leaves the list as it is.
     *
     * @param array<string, mixed> $chosen the options by column, as the filter form sends them
     */
    public function applyFilters(Model $model, array $chosen): Model
    {
        foreach (array_keys($this->filters()) as $column) {
            $options = array_values(array_filter(
                (array) ($chosen[$column] ?? []),
                static fn (mixed $option): bool => is_scalar($option) && $option !== '',
            ));

            if ($options !== []) {
                $model->whereIn($this->qualify($model, $column), $options);
            }
        }

        return $model;
    }

    public function allows(string $page): bool
    {
        return in_array($page, $this->pages, true);
    }

    /**
     * The fields as the shared Field, and the sections that group them, which are none when `fields()` has no section.
     *
     * @return array{list<Field>, list<Section>}
     *
     * @throws ResourceException when a field has an unknown type, or a section a bad count of columns
     */
    private function layout(string $id): array
    {
        $fields   = [];
        $sections = [];
        $loose    = [];

        foreach ($this->fields() as $item) {
            if ($item instanceof Section || $item instanceof SectionBuilder) {
                if ($loose !== []) {
                    $sections[] = new Section(null, null, Section::DEFAULT_COLUMNS, $loose);
                    $loose      = [];
                }

                $section = $item instanceof SectionBuilder ? $item->compile() : $item;
                $own     = array_map(static fn (Field $field): Field => self::withId($field, $id), $section->fields);

                $sections[] = new Section($section->title, $section->instructions, $section->columns, $own);
                array_push($fields, ...$own);

                continue;
            }

            $field    = self::withId($item instanceof FieldBuilder ? $item->compile() : $item, $id);
            $fields[] = $field;
            $loose[]  = $field;
        }

        // Fields beside sections are a group of their own, but with no sections at all the form is one group.
        if ($loose !== [] && $sections !== []) {
            $sections[] = new Section(null, null, Section::DEFAULT_COLUMNS, $loose);
        }

        return [$fields, $sections];
    }

    /**
     * The field with `{id}` in its rules replaced by the ID of the record.
     */
    private static function withId(Field $field, string $id): Field
    {
        $fill = static fn (mixed $rule): mixed => is_string($rule) ? str_replace('{id}', $id, $rule) : $rule;

        return new Field(
            $field->handle,
            $field->type,
            $field->label,
            is_array($field->validate) ? array_map($fill, $field->validate) : $fill($field->validate),
            $field->instructions,
            $field->options,
            $field->full,
        );
    }

    /**
     * A column of the model's table named with the table, so that a join in query() can't make it ambiguous.
     */
    private function qualify(Model $model, string $column): string
    {
        return str_contains($column, '.') ? $column : $model->table . '.' . $column;
    }
}

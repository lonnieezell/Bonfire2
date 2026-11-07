<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Actions;

use Bonfire\Fields\Field;
use Bonfire\Fields\FieldException;
use Bonfire\Fields\FieldTypes;
use Bonfire\Fields\Form;
use Bonfire\Resources\Fields\Field as FieldBuilder;
use Bonfire\Users\User;

/**
 * Something an admin can do to one record from its row, or to the checked
 * records of a list. Register it on a list from a module's `initAdmin()`:
 *
 *     service('actions')->for('users')->add(new BanUsers());
 *
 * `handle()` may run in a queue worker, so it must rely only on the context
 * it is given, not on the request, session or signed-in user.
 */
abstract class Action
{
    public string $label = '';

    /**
     * A Font Awesome class, like 'fas fa-ban'.
     */
    public string $icon = '';

    /**
     * The permission needed to see and run the action. Empty for anyone in the admin area.
     */
    public string $permission = '';

    public Placement $placement = Placement::Both;

    /**
     * Styles the action as destructive.
     */
    public bool $danger = false;

    public Queue $queue      = Queue::Auto;
    private ?bool $hasFields = null;

    /**
     * With `Queue::Auto`, runs on more records than this are queued.
     */
    public int $queueAbove = 500;

    /**
     * The action's URL segment and name on its list, from the class name: `BanUsers` is `ban-users`.
     */
    public function name(): string
    {
        helper('inflector');

        return dasherize(decamelize(class_basename(static::class)));
    }

    /**
     * Asks before running when not null.
     */
    public function confirm(): ?Confirm
    {
        return null;
    }

    /**
     * What the admin is asked for before the action runs, shown in the confirm dialog. Write a field with the builders
     * in `Bonfire\Resources\Fields` or as a `Bonfire\Fields\Field`. `handle()` reads the values with `$context->input()`.
     *
     * @return list<Field|FieldBuilder>
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * Whether `fields()` has anything to ask for. Asked once, since a list renders its buttons for every row.
     */
    public function hasFields(): bool
    {
        return $this->hasFields ??= $this->fields() !== [];
    }

    /**
     * The form of `fields()`.
     *
     * @throws FieldException when a field's options can't be used
     */
    public function form(): Form
    {
        $fields = array_map(static fn (Field|FieldBuilder $field): Field => $field instanceof FieldBuilder ? $field->compile() : $field, $this->fields());

        foreach ($fields as $field) {
            FieldTypes::get($field->type)->check($field);
        }

        return new Form($fields);
    }

    /**
     * What the dialog or confirm page asks: `confirm()`, or just the action's label when it only has fields to ask for.
     */
    public function question(): ?Confirm
    {
        return $this->confirm() ?? ($this->hasFields() ? Confirm::make($this->label) : null);
    }

    /**
     * Whether the action may touch this record. Rejected records are skipped and reported.
     */
    public function allows(User $actor, object $record): bool
    {
        return true;
    }

    abstract public function handle(ActionContext $context): ActionResult;

    /**
     * The data-confirm attributes <x-confirm-dialog> reads to ask before the
     * action runs, or an empty string when it doesn't ask. With fields, the dialog
     * fetches them from the button's `formaction` plus `/fields`.
     */
    public function confirmAttributes(): string
    {
        $confirm = $this->question();

        if ($confirm === null) {
            return '';
        }

        return stringify_attributes(array_filter([
            'data-confirm-fields' => $this->hasFields() ? '1' : '',
            'data-confirm'        => $confirm->title(),
            'data-confirm-body'   => $confirm->text(),
            'data-confirm-button' => $confirm->buttonLabel() ?: $this->label,
            'data-confirm-danger' => $this->danger ? '1' : '',
            'data-confirm-type'   => $confirm->asksToType() ? '1' : '',
        ], static fn (string $value) => $value !== ''));
    }
}

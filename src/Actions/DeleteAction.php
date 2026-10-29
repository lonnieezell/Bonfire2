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

/**
 * Deletes records, a chunk at a time, after asking. A list's delete extends
 * it with how its records are deleted, and usually what they're called.
 */
abstract class DeleteAction extends Action
{
    public string $icon = 'fas fa-trash';
    public bool $danger = true;

    public function __construct()
    {
        $this->label = lang('Bonfire.delete');
    }

    public function confirm(): ?Confirm
    {
        return Confirm::make(lang('Bonfire.deleteSelectedConfirm', [$this->noun()]))
            ->button(lang('Bonfire.delete'));
    }

    public function handle(ActionContext $context): ActionResult
    {
        foreach ($context->chunks() as $records) {
            $this->delete($records);
        }

        return ActionResult::message($this->deleted());
    }

    /**
     * Throws when the records can't be deleted.
     *
     * @param list<object> $records
     */
    abstract protected function delete(array $records): void;

    /**
     * What the records are called in the default wording, plural: 'users'.
     */
    protected function noun(): string
    {
        return lang('Actions.records');
    }

    /**
     * The message once the records are deleted.
     */
    protected function deleted(): string
    {
        return lang('Bonfire.resourcesDeleted', [$this->noun()]);
    }
}

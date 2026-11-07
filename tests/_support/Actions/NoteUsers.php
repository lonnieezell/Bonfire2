<?php

namespace Tests\Support\Actions;

use Bonfire\Actions\Action;
use Bonfire\Actions\ActionContext;
use Bonfire\Actions\ActionResult;
use Bonfire\Actions\Confirm;
use Bonfire\Resources\Fields\Textarea;
use Bonfire\Users\User;

/**
 * A test action that asks for a note, and records the notes it was given.
 */
class NoteUsers extends Action
{
    public string $label      = 'Note';
    public string $permission = 'users.edit';

    /**
     * @var list<string>
     */
    public array $notes = [];

    /**
     * @var list<int>
     */
    public array $touched = [];

    public bool $asks = false;

    public function confirm(): ?Confirm
    {
        return $this->asks ? Confirm::make('Note {count} users?') : null;
    }

    public function fields(): array
    {
        return [Textarea::make('note')->label('Note')->validate('required|max_length[20]')];
    }

    public function allows(User $actor, object $record): bool
    {
        return $actor->id !== $record->id;
    }

    public function handle(ActionContext $context): ActionResult
    {
        $this->notes[] = $context->input('note');

        foreach ($context->entities() as $user) {
            $this->touched[] = $user->id;
        }

        return ActionResult::message('{count} users noted.');
    }
}

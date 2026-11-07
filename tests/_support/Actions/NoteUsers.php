<?php

namespace Tests\Support\Actions;

use Bonfire\Actions\ActionContext;
use Bonfire\Actions\ActionResult;
use Bonfire\Resources\Fields\Textarea;

/**
 * A test action that asks for a note, and records the notes it was given.
 */
class NoteUsers extends TouchUsers
{
    public string $label = 'Note';

    /**
     * @var list<string>
     */
    public array $notes = [];

    public function fields(): array
    {
        return [Textarea::make('note')->label('Note')->validate('required|max_length[20]')];
    }

    public function handle(ActionContext $context): ActionResult
    {
        $this->notes[] = $context->input('note');

        return parent::handle($context);
    }
}

<?php

namespace Tests\Support\Actions;

use Bonfire\Actions\Action;
use Bonfire\Actions\ActionContext;
use Bonfire\Actions\ActionResult;
use Bonfire\Actions\Confirm;
use Bonfire\Users\User;
use RuntimeException;

/**
 * A test action that records which users it handled, and refuses the actor's own record.
 */
class TouchUsers extends Action
{
    public string $label      = 'Touch';
    public string $permission = 'users.edit';

    /**
     * @var list<int>
     */
    public array $touched = [];

    public ?Confirm $asks = null;
    public bool $fails    = false;

    /**
     * Whether it answers with a file of the IDs it touched.
     */
    public bool $writesFile = false;

    /**
     * The file it wrote last.
     */
    public string $file = '';

    public function confirm(): ?Confirm
    {
        return $this->asks;
    }

    public function allows(User $actor, object $record): bool
    {
        return $actor->id !== $record->id;
    }

    public function handle(ActionContext $context): ActionResult
    {
        if ($this->fails) {
            throw new RuntimeException('Touch failed');
        }

        foreach ($context->entities() as $user) {
            $this->touched[] = $user->id;
        }

        if ($this->writesFile) {
            $this->file = tempnam(sys_get_temp_dir(), 'touched');
            file_put_contents($this->file, implode(',', $this->touched));

            return ActionResult::file($this->file, 'touched.txt', '{count} users touched.');
        }

        return ActionResult::message('{count} users touched.');
    }
}

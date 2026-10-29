<?php

namespace Tests\Actions;

use Bonfire\Actions\ActionContext;
use Bonfire\Actions\ActionResult;
use Bonfire\Actions\Confirm;
use Bonfire\Actions\ModelSource;
use Bonfire\Users\Models\UserModel;
use Tests\Support\Actions\TouchUsers;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ActionContextTest extends TestCase
{
    protected $refresh = true;

    public function testRecordsComeInChunksSkippingMissingAndRejectedOnes(): void
    {
        $actor  = $this->createUser();
        $others = [$this->createUser()->id, $this->createUser()->id, $this->createUser()->id];

        $touch    = new TouchUsers();
        $progress = [];
        $context  = new ActionContext(
            new ModelSource(UserModel::class),
            $touch,
            $actor,
            [$actor->id, ...$others, 999],
            chunkSize: 2,
            afterChunk: static function (int $processed, int $skipped) use (&$progress) {
                $progress[] = [$processed, $skipped];
            },
        );

        $result = $touch->handle($context);

        $this->assertSame($others, $touch->touched);
        $this->assertSame(3, $context->processed());
        $this->assertSame(2, $context->skipped());
        $this->assertSame([[1, 1], [3, 1], [3, 2]], $progress);
        $this->assertSame('3 users touched.', $result->text($context->processed()));
    }

    public function testConfirmFillsTheCountIntoItsTitle(): void
    {
        $confirm = Confirm::make('Ban {count} users?')->body('They are signed out.')->button('Ban')->typeToConfirm();

        $this->assertSame('Ban 3 users?', $confirm->title(3));
        $this->assertSame('Ban {count} users?', $confirm->title());
        $this->assertSame('They are signed out.', $confirm->text());
        $this->assertSame('Ban', $confirm->buttonLabel());
        $this->assertTrue($confirm->asksToType());
        $this->assertFalse(Confirm::make('Sure?')->asksToType());
    }

    public function testResultsSayWhetherTheyFailed(): void
    {
        $this->assertFalse(ActionResult::message('Done')->failed);
        $this->assertTrue(ActionResult::error('{count} failed')->failed);
        $this->assertSame('2 failed', ActionResult::error('{count} failed')->text(2));
    }
}

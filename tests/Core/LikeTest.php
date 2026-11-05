<?php

namespace Tests\Core;

use Bonfire\Core\Like;
use CodeIgniter\Database\BaseConnection;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class LikeTest extends TestCase
{
    private function connection(string $escape = '!'): BaseConnection
    {
        $db                 = $this->createStub(BaseConnection::class);
        $db->likeEscapeChar = $escape;

        return $db;
    }

    public function testWildcardsAreEscapedWithTheConnectionsEscapeCharacter(): void
    {
        $this->assertSame('100!% sure!_ok', Like::escape('100% sure_ok', $this->connection()));
        $this->assertSame('100\% sure\_ok', Like::escape('100% sure_ok', $this->connection('\\')));
    }

    public function testTheEscapeCharacterIsEscapedToo(): void
    {
        $this->assertSame('!!!%', Like::escape('!%', $this->connection()));
    }

    public function testATermWithoutSpecialCharactersIsUnchanged(): void
    {
        $this->assertSame("zebra's", Like::escape("zebra's", $this->connection()));
    }
}

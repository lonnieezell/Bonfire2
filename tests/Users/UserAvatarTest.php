<?php

namespace Tests\Users;

use Tests\Support\TestCase;

/**
 * @internal
 */
final class UserAvatarTest extends TestCase
{
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Users.useGravatar', false);
    }

    public function testAvatarLinkUsesTheAvatarDirectory(): void
    {
        setting('Users.avatarDirectory', 'uploads/elsewhere');
        $user         = $this->createUser();
        $user->avatar = '1_abc.jpg';

        $this->assertSame(base_url('uploads/elsewhere/1_abc.jpg'), $user->avatarLink());
    }

    public function testUsernameBasisTakesInitialsFromTheUsername(): void
    {
        setting('Users.avatarNameBasis', 'username');
        $user = $this->createUser(['username' => 'zoe', 'email' => 'abby@example.com']);

        $this->assertSame('ZO', trim(strip_tags($user->renderAvatar())));
    }
}

<?php

namespace Tests\Bonfire\Users;

use App\Entities\User;
use Tests\Support\TestCase;

class AdminSettingsTest extends TestCase
{
    protected $refresh = true;

    /**
     * @var User
     */
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
    }

    public function testCanSeePage()
    {
        $result = $this->actingAs($this->user)
            ->get('/admin/settings/users');

        $result->assertStatus(200);
        $result->assertSee('Registration');
    }

    public function testSubmitForm()
    {
        $result = $this->actingAs($this->user)
            ->post('/admin/settings/users', [
                'allowRegistration' => 1,
                'emailActivation' => 1,
                'allowRemember' => 'on',
                'rememberLength' => 55,
                'email2FA' => 'on',
                'minimumPasswordLength' => 10,
                'validators' => [
                    'Sparks\Shield\Authentication\Passwords\CompositionValidator'
                ],
                'defaultGroup' => 'developer',
            ]);

        $result->assertRedirect();

        $this->assertTrue(setting('Auth.allowRegistration'));
        $this->assertEquals(10, setting('Auth.minimumPasswordLength'));
        $this->assertEquals('developer', setting('AuthGroups.defaultGroup'));
        $this->assertEquals(['Sparks\Shield\Authentication\Passwords\CompositionValidator'], setting('Auth.passwordValidators'));
        $this->assertEquals(['login' => true, 'register' => true], setting('Auth.actions'));
    }
}

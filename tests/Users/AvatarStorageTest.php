<?php

namespace Tests\Users;

use Bonfire\Users\Libraries\AvatarStorage;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\HTTP\Files\UploadedFile;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class AvatarStorageTest extends TestCase
{
    protected $refresh = true;
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $relativeDir = 'uploads/avatars-test-' . bin2hex(random_bytes(4));
        $this->dir   = FCPATH . $relativeDir;
        setting('Users.avatarDirectory', $relativeDir);
    }

    protected function tearDown(): void
    {
        helper('filesystem');
        delete_files($this->dir, true);

        parent::tearDown();
    }

    private function avatarFor(User $user, string $name): void
    {
        if (! is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }
        file_put_contents($this->dir . '/' . $name, 'img');
        model(UserModel::class)->update($user->id, ['avatar' => $name]);
    }

    private function uploadedImage(int $size): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'bf-avatar');
        imagejpeg(imagecreatetruecolor($size, $size), $path);

        // The framework refuses to move anything PHP did not receive as a real HTTP upload.
        return new class ($path, 'me.jpg', 'image/jpeg', filesize($path), UPLOAD_ERR_OK) extends UploadedFile {
            public function move(string $targetPath, ?string $name = null, bool $overwrite = false): bool
            {
                return rename($this->getPathname(), rtrim($targetPath, '/') . '/' . $name);
            }
        };
    }

    public function testDirectoryFallsBackToDefault(): void
    {
        setting('Users.avatarDirectory', null);

        $this->assertSame(FCPATH . 'uploads/avatars', (new AvatarStorage())->directory());
    }

    public function testDeleteRemovesTheFile(): void
    {
        mkdir($this->dir, 0755, true);
        file_put_contents($this->dir . '/1_abc.jpg', 'img');

        $this->assertTrue((new AvatarStorage())->delete('1_abc.jpg'));
        $this->assertFileDoesNotExist($this->dir . '/1_abc.jpg');
    }

    public function testDeleteReportsFilesThatAreAlreadyGone(): void
    {
        $this->assertFalse((new AvatarStorage())->delete('missing.jpg'));
        $this->assertFalse((new AvatarStorage())->delete(null));
    }

    public function testStoreMovesTheUploadAndReplacesThePreviousFile(): void
    {
        $user = $this->createUser();
        $this->avatarFor($user, 'old.jpg');
        $user = model(UserModel::class)->find($user->id);

        $filename = (new AvatarStorage())->store($this->uploadedImage(20), $user);

        $this->assertMatchesRegularExpression('/^' . $user->id . '_[A-Za-z0-9]{5}\.jpg$/', $filename);
        $this->assertFileExists($this->dir . '/' . $filename);
        $this->assertFileDoesNotExist($this->dir . '/old.jpg');
    }

    public function testStoreResizesLargeImagesWhenEnabled(): void
    {
        setting('Users.avatarResize', true);
        setting('Users.avatarSize', 10);
        $user = $this->createUser();

        $filename = (new AvatarStorage())->store($this->uploadedImage(40), $user);

        [$width, $height] = getimagesize($this->dir . '/' . $filename);
        $this->assertSame([10, 10], [$width, $height]);
    }

    public function testPurgingSeveralUsersRemovesEveryAvatar(): void
    {
        $first  = $this->createUser();
        $second = $this->createUser();
        $this->avatarFor($first, 'first.jpg');
        $this->avatarFor($second, 'second.jpg');

        model(UserModel::class)->delete([$first->id, $second->id], true);

        $this->assertFileDoesNotExist($this->dir . '/first.jpg');
        $this->assertFileDoesNotExist($this->dir . '/second.jpg');
    }

    public function testPurgingSeveralUsersRemovesEveryUsersMeta(): void
    {
        $first  = $this->createUser();
        $second = $this->createUser();
        $first->saveMeta('foo', 'one');
        $second->saveMeta('foo', 'two');

        model(UserModel::class)->delete([$first->id, $second->id], true);

        $this->dontSeeInDatabase('meta_info', ['class' => User::class, 'key' => 'foo']);
    }

    public function testSoftDeleteKeepsTheAvatar(): void
    {
        $user = $this->createUser();
        $this->avatarFor($user, 'kept.jpg');

        model(UserModel::class)->delete($user->id);

        $this->assertFileExists($this->dir . '/kept.jpg');
    }
}

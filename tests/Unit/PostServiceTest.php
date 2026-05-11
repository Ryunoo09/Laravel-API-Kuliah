<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ============================================================
 *  Unit Test: PostService
 * ============================================================
 *
 *  Menguji business logic di PostService secara terisolasi.
 *  Tidak membutuhkan HTTP request — langsung panggil method service.
 *
 *  Unit test fokus pada:
 *  - Apakah logika role/ownership sudah benar?
 *  - Apakah service melempar exception yang tepat?
 * ============================================================
 */
class PostServiceTest extends TestCase
{
    private PostService $postService;

    protected function setUp(): void
    {
        parent::setUp(); // Ini juga memanggil RefreshDatabase dari TestCase
        $this->postService = new PostService();
    }

    /** Admin bisa melihat semua post dari semua user */
    #[Test]
    public function admin_gets_all_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $userA = User::factory()->asUser()->create();
        $userB = User::factory()->asUser()->create();

        Post::factory()->count(3)->create(['user_id' => $userA->id]);
        Post::factory()->count(2)->create(['user_id' => $userB->id]);

        $posts = $this->postService->getAllPosts($admin);

        $this->assertCount(5, $posts);
    }

    /** User biasa hanya melihat post miliknya sendiri */
    #[Test]
    public function regular_user_gets_only_own_posts(): void
    {
        $user  = User::factory()->asUser()->create();
        $other = User::factory()->asUser()->create();

        Post::factory()->count(3)->create(['user_id' => $user->id]);
        Post::factory()->count(2)->create(['user_id' => $other->id]);

        $posts = $this->postService->getAllPosts($user);

        $this->assertCount(3, $posts);
        // Pastikan semua post yang tampil milik user sendiri
        $posts->each(fn ($p) => $this->assertEquals($user->id, $p->user_id));
    }

    /** Service melempar HTTP 403 saat non-owner mencoba update */
    #[Test]
    public function update_throws_403_for_non_owner(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $owner   = User::factory()->asUser()->create();
        $other   = User::factory()->asUser()->create();
        $post    = Post::factory()->create(['user_id' => $owner->id]);

        $this->postService->updatePost($post, ['title' => 'Hack'], $other);
    }

    /** Service melempar HTTP 403 saat user biasa mencoba delete */
    #[Test]
    public function delete_throws_403_for_non_admin(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $user = User::factory()->asUser()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->postService->deletePost($post, $user);
    }

    /** Admin dapat menghapus post siapapun tanpa exception */
    #[Test]
    public function admin_can_delete_any_post(): void
    {
        $admin = User::factory()->admin()->create();
        $user  = User::factory()->asUser()->create();
        $post  = Post::factory()->create(['user_id' => $user->id]);

        $result = $this->postService->deletePost($post, $admin);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    /** createPost menyimpan user_id dari user yang login */
    #[Test]
    public function create_post_assigns_correct_user_id(): void
    {
        $user = User::factory()->asUser()->create();

        $post = $this->postService->createPost([
            'title'   => 'Test Judul',
            'content' => 'Konten test.',
            'status'  => 'draft',
        ], $user);

        $this->assertEquals($user->id, $post->user_id);
        $this->assertDatabaseHas('posts', [
            'title'   => 'Test Judul',
            'user_id' => $user->id,
        ]);
    }
}

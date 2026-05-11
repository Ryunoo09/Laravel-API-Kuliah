<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ============================================================
 *  Feature Test: Post API Endpoints
 * ============================================================
 *
 *  Skenario yang diuji:
 *  [401] Unauthenticated → tanpa token / tanpa actingAs
 *  [201] Store (Create) → authenticated user berhasil buat post
 *  [200] Index (List)   → authenticated user melihat post-nya
 *  [200] Show (Detail)  → authenticated user melihat detail post
 *  [403] Forbidden      → user A mencoba edit/hapus post user B
 *  [404] Not Found      → request ke ID yang tidak ada
 *  [403] Delete bukan Admin → user biasa tidak boleh hapus post
 *  [200] Admin Delete   → admin berhasil menghapus post
 *  [422] Validation     → field wajib kosong
 *
 *  RefreshDatabase (via TestCase) menjamin DB bersih tiap test.
 * ============================================================
 */
class PostTest extends TestCase
{
    // ─────────────────────────────────────────────────────────
    //  SKENARIO 401 — UNAUTHENTICATED
    // ─────────────────────────────────────────────────────────

    #[Test]
    public function unauthenticated_user_cannot_list_posts(): void
    {
        $response = $this->getJson('/api/v1/posts');

        $response->assertUnauthorized(); // HTTP 401
    }

    #[Test]
    public function unauthenticated_user_cannot_create_post(): void
    {
        $response = $this->postJson('/api/v1/posts', [
            'title'   => 'Post Tanpa Auth',
            'content' => 'Ini seharusnya gagal.',
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function unauthenticated_user_cannot_update_post(): void
    {
        // Buat post langsung via factory (tidak perlu auth)
        $post = Post::factory()->create();

        $response = $this->putJson("/api/v1/posts/{$post->id}", [
            'title' => 'Coba Update Tanpa Token',
        ]);

        $response->assertUnauthorized();
    }

    // ─────────────────────────────────────────────────────────
    //  SKENARIO 201 — AUTHENTICATED: CREATE POST
    // ─────────────────────────────────────────────────────────

    /**
     * User yang login berhasil membuat post → 201 Created
     * Validasi struktur JSON sesuai PostResource
     */
    #[Test]
    public function authenticated_user_can_create_post(): void
    {
        // Arrange: siapkan user dengan factory
        $user = User::factory()->asUser()->create();

        // Act: simulasi login dengan actingAs(), kirim POST request
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/posts', [
                'title'   => 'Belajar Automated Testing',
                'content' => 'Testing adalah kunci kualitas software.',
                'status'  => 'published',
            ]);

        // Assert: status 201 dan struktur JSON sesuai PostResource
        $response
            ->assertCreated()                  // HTTP 201
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'status',
                    'content',
                    'created_at',
                    'updated_at',
                    'link',
                ],
            ])
            ->assertJsonPath('data.title', 'Belajar Automated Testing')
            ->assertJsonPath('data.status', 'published');

        // Pastikan data benar-benar tersimpan di DB
        $this->assertDatabaseHas('posts', [
            'title'   => 'Belajar Automated Testing',
            'user_id' => $user->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    //  SKENARIO 200 — AUTHENTICATED: LIST POSTS
    // ─────────────────────────────────────────────────────────

    /** User yang login hanya melihat post miliknya sendiri */
    #[Test]
    public function authenticated_user_can_list_own_posts(): void
    {
        // Arrange: 2 user dengan masing-masing 2 post
        $userA = User::factory()->asUser()->create();
        $userB = User::factory()->asUser()->create();

        Post::factory()->count(2)->create(['user_id' => $userA->id]);
        Post::factory()->count(3)->create(['user_id' => $userB->id]);

        // Act: userA login dan ambil list
        $response = $this->actingAs($userA, 'sanctum')
            ->getJson('/api/v1/posts');

        // Assert: hanya 2 post milik userA yang tampil
        $response
            ->assertOk()                       // HTTP 200
            ->assertJsonCount(2, 'data');
    }

    /** Admin dapat melihat SEMUA post dari semua user */
    #[Test]
    public function admin_can_see_all_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $userA = User::factory()->asUser()->create();
        $userB = User::factory()->asUser()->create();

        Post::factory()->count(2)->create(['user_id' => $userA->id]);
        Post::factory()->count(3)->create(['user_id' => $userB->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/posts');

        $response
            ->assertOk()
            ->assertJsonCount(5, 'data');       // total 5 post dari semua user
    }

    // ─────────────────────────────────────────────────────────
    //  SKENARIO 200 — SHOW (DETAIL POST)
    // ─────────────────────────────────────────────────────────

    /** User melihat detail post miliknya sendiri */
    #[Test]
    public function user_can_view_own_post(): void
    {
        $user = User::factory()->asUser()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/posts/{$post->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $post->id)
            ->assertJsonPath('data.title', $post->title);
    }

    // ─────────────────────────────────────────────────────────
    //  SKENARIO 403 — FORBIDDEN (OTORISASI / POLICY)
    // ─────────────────────────────────────────────────────────

    /**
     * User A mencoba mengedit post milik User B → 403 Forbidden
     * Logika otorisasi ada di PostService::updatePost()
     */
    #[Test]
    public function user_cannot_update_post_of_another_user(): void
    {
        // Arrange: userA punya post; userB yang akan mencoba edit
        $userA = User::factory()->asUser()->create();
        $userB = User::factory()->asUser()->create();
        $postOfUserA = Post::factory()->create(['user_id' => $userA->id]);

        // Act: userB coba edit post milik userA
        $response = $this->actingAs($userB, 'sanctum')
            ->putJson("/api/v1/posts/{$postOfUserA->id}", [
                'title' => 'Upaya Tidak Sah',
            ]);

        // Assert: Laravel Service lempar abort(403)
        $response->assertForbidden(); // HTTP 403
    }

    /** User A mencoba melihat detail post milik User B → 403 Forbidden */
    #[Test]
    public function user_cannot_view_post_of_another_user(): void
    {
        $userA = User::factory()->asUser()->create();
        $userB = User::factory()->asUser()->create();
        $postOfUserA = Post::factory()->create(['user_id' => $userA->id]);

        $response = $this->actingAs($userB, 'sanctum')
            ->getJson("/api/v1/posts/{$postOfUserA->id}");

        $response->assertForbidden();
    }

    /**
     * User biasa (bukan admin) mencoba menghapus post → 403 Forbidden
     * (delete di project ini hanya boleh dilakukan admin)
     */
    #[Test]
    public function regular_user_cannot_delete_any_post(): void
    {
        $user = User::factory()->asUser()->create();
        // Post milik user sendiri pun tidak bisa dihapus oleh regular user
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/posts/{$post->id}");

        $response->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────
    //  SKENARIO 404 — NOT FOUND
    // ─────────────────────────────────────────────────────────

    /** Request ke ID post yang tidak ada di DB → 404 Not Found */
    #[Test]
    public function returns_404_for_nonexistent_post(): void
    {
        $user = User::factory()->asUser()->create();

        // ID 9999 tidak ada di SQLite :memory: (DB kosong setiap test)
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/posts/9999');

        $response->assertNotFound(); // HTTP 404
    }

    /** Update ke post yang tidak ada → 404 Not Found */
    #[Test]
    public function returns_404_when_updating_nonexistent_post(): void
    {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/posts/9999', [
                'title' => 'Tidak Akan Berhasil',
            ]);

        $response->assertNotFound();
    }

    // ─────────────────────────────────────────────────────────
    //  SKENARIO BONUS — ADMIN DELETE
    // ─────────────────────────────────────────────────────────

    /** Admin berhasil menghapus post siapapun → 200 + {deleted: true} */
    #[Test]
    public function admin_can_delete_any_post(): void
    {
        $admin  = User::factory()->admin()->create();
        $user   = User::factory()->asUser()->create();
        $post   = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/posts/{$post->id}");

        $response
            ->assertOk()
            ->assertJson([
                'id'      => $post->id,
                'deleted' => true,
            ]);

        // Pastikan benar-benar terhapus dari DB
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    // ─────────────────────────────────────────────────────────
    //  SKENARIO VALIDASI — 422 UNPROCESSABLE
    // ─────────────────────────────────────────────────────────

    /** Create post tanpa 'title' yang wajib → 422 Validation Error */
    #[Test]
    public function cannot_create_post_without_required_title(): void
    {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/posts', [
                // 'title' sengaja dihilangkan
                'content' => 'Konten tanpa judul.',
            ]);

        $response
            ->assertUnprocessable()            // HTTP 422
            ->assertJsonValidationErrors(['title']);
    }
}

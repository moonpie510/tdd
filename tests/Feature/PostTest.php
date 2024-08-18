<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /** @test */

    public function a_post_can_be_stored()
    {
        $this->withoutExceptionHandling();

        $file = File::create('my_image.jpg');

        $data = [
            'title' => 'test title',
            'description' => 'test description',
            'image' => $file,
        ];

        $response = $this->post('/posts', $data);
        $response->assertOk(200);

        $this->assertDatabaseCount('posts', 1);

        $post = Post::first();

        $this->assertEquals($data['title'], $post->title);
        $this->assertEquals($data['description'], $post->description);
        $this->assertEquals('images/' . $file->hashName(), $post->image_url);
        Storage::disk('local')->assertExists($post->image_url);
    }

    /** @test */

    public function attribute_title_is_required_for_storing_post()
    {
        $data = [
            'title' => '',
            'description' => 'test description',
            'image' => ''
        ];

        $response = $this->post('/posts', $data);
        $response->assertRedirect();
        $response->assertInvalid();
    }

    /** @test */

    public function attribute_image_is_file_for_storing_post()
    {
        $data = [
            'title' => 'title',
            'description' => 'test description',
            'image' => 'jdjdhf'
        ];

        $response = $this->post('/posts', $data);
        $response->assertRedirect();
        $response->assertInvalid('image');
    }

    /** @test */
    public function a_post_can_be_updated()
    {
        $this->withoutExceptionHandling();

        $post = Post::factory()->create();

        $file = File::create('my_image.jpg');
        $data = [
            'title' => 'title edited',
            'description' => 'test description edited',
            'image' => $file
        ];

        $res = $this->patch('/posts/' . $post->id, $data);

        $res->assertOk();
        $updatedPost = Post::first();
        $this->assertEquals($data['title'], $updatedPost->title);
        $this->assertEquals($data['description'], $updatedPost->description);
        $this->assertEquals('images/' . $file->hashName(), $updatedPost->image_url);

        $this->assertEquals($post->id, $updatedPost->id);
    }

    /** @test */
    public function responce_for_route_posts_index_is_view_index_post_index_with_posts()
    {
        $this->withoutExceptionHandling();
        $posts = Post::factory(10)->create();

        $res = $this->get('/posts');

        $res->assertViewIs('posts.index');
        $res->assertSeeText('View page');

        $titles = $posts->pluck('title')->toArray();
        $res->assertSeeText($titles);
    }

    /** @test */

    public function responce_for_route_show_index_is_view_index_post_show_with__single_post()
    {
        $this->withoutExceptionHandling();
        $post = Post::factory()->create();

        $res = $this->get('/posts/' . $post->id);
        $res->assertViewIs('posts.show');
        $res->assertSeeText('Show page');
        $res->assertSeeText($post->title);
        $res->assertSeeText($post->description);
    }

    /** @test */
    public function a_post_can_be_deleted_by_auth_user()
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $res = $this->actingAs($user)->delete('/posts/' . $post->id);

        $res->assertOk();
        $this->assertDatabaseCount('posts', 0);
    }

    /** @test */
    public function a_post_can_be_deleted_by_only_auth_user()
    {
        $post = Post::factory()->create();
        $res = $this->delete('/posts/' . $post->id);

        $res->assertRedirect();
        $this->assertDatabaseCount('posts', 1);
    }



}

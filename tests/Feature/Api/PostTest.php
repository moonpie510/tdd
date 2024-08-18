<?php

namespace Feature\Api;

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
        $this->withHeaders([
            'accept' => 'application/json',
        ]);
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

        $response = $this->post('/api/posts', $data);

        $this->assertDatabaseCount('posts', 1);

        $post = Post::first();

        $this->assertEquals($data['title'], $post->title);
        $this->assertEquals($data['description'], $post->description);
        $this->assertEquals('images/' . $file->hashName(), $post->image_url);
        Storage::disk('local')->assertExists($post->image_url);

        $response->assertJson([
            'id' => $post->id,
            'title' => $post->title,
            'description' => $post->description,
            'image_url' => $post->image_url,
        ]);
    }

    /** @test */
    public function attribute_title_is_required_for_storing_post()
    {
        $data = [
            'title' => '',
            'description' => 'test description',
            'image' => ''
        ];

        $response = $this->post('/api/posts', $data);
        $response->assertStatus(422);
        $response->assertInvalid('title');
    }

    /** @test */

    public function attribute_image_is_file_for_storing_post()
    {
        $data = [
            'title' => 'title',
            'description' => 'test description',
            'image' => 'jdjdhf'
        ];

        $response = $this->post('/api/posts', $data);
        $response->assertStatus(422);
        $response->assertInvalid('image');
        $response->assertJsonValidationErrors([
            'image' => 'The image field must be a file.'
        ]);
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

        $res = $this->patch('/api/posts/' . $post->id, $data);

        $res->assertJson([
            'id' => $post->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'image_url' => 'images/' . $file->hashName(),
        ]);

    }

    /** @test */
    public function responce_for_route_posts_index_is_view_index_post_index_with_posts()
    {
        $this->withoutExceptionHandling();
        $posts = Post::factory(10)->create();

        $res = $this->get('/api/posts');

        $res->assertOk();

        $json = $posts->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'description' => $post->description,
                'image_url' => $post->image_url,
            ];
        })->toArray();

        $res->assertJson($json);

    }

}

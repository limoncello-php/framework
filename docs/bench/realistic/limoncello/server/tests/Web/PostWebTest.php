<?php namespace Tests\Web;

use App\Json\Schemas\PostSchema;
use Tests\TestCase;

/**
 * @package Tests
 */
class PostWebTest extends TestCase
{
    const RESOURCES_URL = '/posts';

    /**
     * Test post 'create post' form data.
     *
     * @return void
     */
    public function testPostCreateFormWithValidData(): void
    {
        $data = [
            PostSchema::ATTR_TEXT       => 'text',
            PostSchema::ATTR_TITLE      => 'title',
            PostSchema::ATTR_CREATED_AT => '2001-02-03',
        ];

        $response = $this->post(self::RESOURCES_URL . 10 . '/create', $data);

        // check we've got redirected on success
        $this->assertEquals(200, $response->getStatusCode());
    }
}

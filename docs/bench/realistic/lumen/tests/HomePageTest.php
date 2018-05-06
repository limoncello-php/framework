<?php

class HomePageTest extends TestCase
{
    /**
     * @return void
     */
    public function testCreatePost(): void
    {
        $this->post('/posts10/create', [
                'title'      => 'post_title',
                'text'       => 'post_text',
                'created-at' => '2100-01-01',
            ]);

        $this->assertEquals(200, $this->response->status());
        $this->assertNotEmpty($this->response->getContent());
    }
}

<?php
namespace Tests\Feature\Http\View\Admin;

use Tests\Psr4\Concerns\AuthConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class SmsCodesTest extends HttpTestCase
{
    use AuthConcern;

    /** @test */
    public function it_loads()
    {
        // given
        $user = $this->factory->admin();
        $this->actingAs($user);

        // when
        $response = $this->get('/admin/sms_codes');

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains('Panel Admina', $response->getContent());
        $this->assertContains(
            '<div class="title is-4">Kody SMS do wykorzystania',
            $response->getContent()
        );
    }
}

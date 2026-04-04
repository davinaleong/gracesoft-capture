<?php

test('blog index is public and shows updates in descending date order', function () {
    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSeeInOrder([
            'GraceSoft Capture Is Live: Launched Yesterday',
            '24 Hours to Launch: Product Polish and Readiness',
            'From Beta to Billing-Ready in One Focused Sprint',
            'The Build-Up to Launch: Security, Compliance, and Control',
        ]);
});

test('blog post detail renders markdown content and share section', function () {
    $this->get(route('blog.show', ['slug' => 'security-center-audit-timeline']))
        ->assertOk()
        ->assertSee('What we shipped before launch')
        ->assertSee('Share this update')
        ->assertSee('https://www.linkedin.com/sharing/share-offsite/?url=', false);
});

test('unknown blog post returns 404', function () {
    $this->get(route('blog.show', ['slug' => 'missing-post']))
        ->assertNotFound();
});

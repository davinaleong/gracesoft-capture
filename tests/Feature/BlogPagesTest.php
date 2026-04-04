<?php

test('blog index is public and shows updates in descending date order', function () {
    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSeeInOrder([
            'Security Center Audit Timeline Improvements',
            'Shared Inbox Performance Upgrades',
        ]);
});

test('blog post detail renders markdown content and share section', function () {
    $this->get(route('blog.show', ['slug' => 'security-center-audit-timeline']))
        ->assertOk()
        ->assertSee("What's new", false)
        ->assertSee('Share this update')
        ->assertSee('https://www.linkedin.com/sharing/share-offsite/?url=', false);
});

test('unknown blog post returns 404', function () {
    $this->get(route('blog.show', ['slug' => 'missing-post']))
        ->assertNotFound();
});

<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('landing page for guests does not expose guarded navigation links', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee(route('manage.forms.index'), false)
        ->assertDontSee(route('inbox.index'), false)
        ->assertDontSee(route('integrations.index'), false)
        ->assertDontSee(route('collaborators.index'), false)
        ->assertDontSee(route('settings.security.index'), false);
});

test('billing success page for guests does not expose guarded links', function () {
    $this->get(route('billing.success'))
        ->assertOk()
        ->assertSee('Log in to continue')
        ->assertDontSee(route('manage.forms.index'), false)
        ->assertDontSee(route('integrations.index'), false);
});

test('support page for guests does not expose guarded app navigation links', function () {
    $this->get(route('support.create'))
        ->assertOk()
        ->assertDontSee(route('manage.forms.index'), false)
        ->assertDontSee(route('inbox.index'), false)
        ->assertDontSee(route('integrations.index'), false)
        ->assertDontSee(route('collaborators.index'), false);
});

test('help guide page is public and plan-aware', function () {
    $this->get(route('help.index'))
        ->assertOk()
        ->assertSee('Help Guide for Office Teams')
        ->assertSee('Free')
        ->assertSee('Growth')
        ->assertSee('Pro');
});

test('privacy policy page is public', function () {
    $this->get(route('legal.privacy'))
        ->assertOk()
        ->assertSee('Privacy Policy');
});

test('terms and conditions page is public', function () {
    $this->get(route('legal.terms'))
        ->assertOk()
        ->assertSee('Terms and Conditions');
});

<?php

test('the root redirects to the admin panel', function () {
    $response = $this->get('/');

    $response->assertRedirect('/admin');
});

<?php

test('the home page sends visitors to Nova', function () {
    $response = $this->get('/');

    $response->assertRedirect('/nova');
});

<?php

use Illuminate\Support\Facades\Route;

Route::get('/print/{name?}', function() {
    return [
        'name' => request('name')
    ];
});
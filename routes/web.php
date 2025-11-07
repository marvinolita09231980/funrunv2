<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/register/register-form');
});

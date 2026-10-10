<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/syndic/login');
});

Route::get('/login', function () {
    return redirect('/syndic/login');
})->name('login');

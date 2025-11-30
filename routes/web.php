<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('usersurvey');
});

Route::view('/admin', 'admin');
Route::view('/usersurvey', 'usersurvey');

require __DIR__.'/auth.php';

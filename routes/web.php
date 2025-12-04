<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('usersurvey');
});

Route::view('/superadmin', 'superadmin');
Route::view('/usersurvey', 'usersurvey');

require __DIR__.'/auth.php';

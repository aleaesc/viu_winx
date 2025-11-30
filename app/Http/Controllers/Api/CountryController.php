<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;

class CountryController extends Controller
{
    public function index()
    {
        return Country::select('id','name','iso_code')->orderBy('name')->get();
    }
}

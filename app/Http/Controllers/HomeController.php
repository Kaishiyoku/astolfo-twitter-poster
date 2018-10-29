<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        return response()->json((new Carbon())->toDateTimeString());
    }
}

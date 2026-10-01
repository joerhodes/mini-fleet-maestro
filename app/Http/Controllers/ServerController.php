<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ServerController extends Controller
{
    public function __invoke(): View
    {
        return view('servers.index');
    }
}

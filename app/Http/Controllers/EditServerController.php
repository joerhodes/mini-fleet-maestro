<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Contracts\View\View;

class EditServerController extends Controller
{
    public function __invoke(?Server $server = null): View
    {
        return view('servers.edit', ['server' => $server]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\Request;

class DashBoardController extends Controller
{
    /**
     * Get the dashboard data and view.
     */
    public function __invoke(Request $request)
    {
        $servers = Server::all();
        return view('dashboard', compact('servers'));
    }
}

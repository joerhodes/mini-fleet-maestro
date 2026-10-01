<?php

namespace App\Http\Controllers;

use App\Services\AnsibleRoleRegistry;
use Illuminate\Contracts\View\View;

class RoleController extends Controller
{
    public function __invoke(AnsibleRoleRegistry $registry): View
    {
        return view('roles.index', ['roles' => $registry->listing()]);
    }
}

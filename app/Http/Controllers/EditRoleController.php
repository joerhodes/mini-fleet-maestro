<?php

namespace App\Http\Controllers;

use App\Services\AnsibleRoleRegistry;
use Illuminate\Contracts\View\View;

class EditRoleController extends Controller
{
    public function __invoke(string $role, AnsibleRoleRegistry $registry): View
    {
        abort_unless($registry->discovered()->contains($role), 404);

        return view('roles.edit', ['role' => $role]);
    }
}

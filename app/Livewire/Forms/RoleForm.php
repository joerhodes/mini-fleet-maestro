<?php

namespace App\Livewire\Forms;

use App\Actions\Roles\SaveRole;
use App\Models\Role;
use App\Services\AnsibleRoleRegistry;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

class RoleForm extends Form
{
    #[Locked]
    public string $role = '';

    public string $label = '';

    public bool $alwaysApply = false;

    #[Locked]
    public bool $isRegistered = false;

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in(app(AnsibleRoleRegistry::class)->discovered())],
            'label' => ['required', 'string', 'max:255'],
            'alwaysApply' => ['boolean'],
        ];
    }

    public function load(string $role): void
    {
        $model = Role::find($role);

        $this->role = $role;
        $this->label = $model->label ?? '';
        $this->alwaysApply = (bool) ($model->always_apply ?? false);
        $this->isRegistered = $model !== null;
    }

    public function save(): Role
    {
        $this->validate();

        $model = app(SaveRole::class)($this->role, $this->label, $this->alwaysApply);

        $this->isRegistered = true;

        return $model;
    }
}

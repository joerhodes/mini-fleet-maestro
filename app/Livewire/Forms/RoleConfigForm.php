<?php

namespace App\Livewire\Forms;

use App\Actions\Roles\SetRoleConfig;
use App\Actions\Roles\UpdateRoleConfig;
use App\Models\RoleConfig;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

class RoleConfigForm extends Form
{
    #[Locked]
    public string $role = '';

    #[Locked]
    public ?int $configId = null;

    public string $key = '';

    public string $value = '';

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('role_configs', 'key')->where('role', $this->role)->ignore($this->configId),
            ],
            'value' => ['required', 'string'],
        ];
    }

    public function create(string $role): void
    {
        $this->resetValidation();

        $this->role = $role;
        $this->configId = null;
        $this->key = '';
        $this->value = '';
    }

    public function load(RoleConfig $config): void
    {
        $this->resetValidation();

        $this->role = $config->role;
        $this->configId = $config->id;
        $this->key = $config->key;
        $this->value = $config->value;
    }

    public function save(): RoleConfig
    {
        $this->validate();

        if ($this->configId === null) {
            return app(SetRoleConfig::class)($this->role, $this->key, $this->value);
        }

        $config = RoleConfig::where('role', $this->role)->findOrFail($this->configId);

        return app(UpdateRoleConfig::class)($config, $this->key, $this->value);
    }
}

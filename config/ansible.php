<?php

// config/ansible.php
return [
    'paths' => [
        'root' => resource_path('ansible'),
        'roles' => resource_path('ansible/roles'),
        'playbooks' => resource_path('ansible/playbooks'),
        'inventory' => storage_path('app/private/ansible'),
    ],

    'binary' => env('ANSIBLE_PLAYBOOK_BIN', 'ansible-playbook'),

    'timeout' => (int) env('ANSIBLE_TIMEOUT', 1800),
];

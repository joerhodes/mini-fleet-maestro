<?php

// config/connectivity.php
return [
    'ping_binary' => env('CONNECTIVITY_PING_BINARY', '/sbin/ping'),

    'ping_timeout' => (int) env('CONNECTIVITY_PING_TIMEOUT', 2),

    'ansible_timeout' => (int) env('CONNECTIVITY_ANSIBLE_TIMEOUT', 5),

    'ssh' => [
        'timeout' => (int) env('CONNECTIVITY_SSH_TIMEOUT', 5),
        'identity_file' => env('CONNECTIVITY_SSH_IDENTITY_FILE', null),
        'known_hosts' => env('CONNECTIVITY_SSH_KNOWN_HOSTS', null),
    ],
];

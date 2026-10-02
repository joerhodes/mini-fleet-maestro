<?php

// config/connectivity.php
return [
    'ping_timeout' => (int) env('CONNECTIVITY_PING_TIMEOUT', 2),

    'ssh_timeout' => (int) env('CONNECTIVITY_SSH_TIMEOUT', 5),

    'ansible_timeout' => (int) env('CONNECTIVITY_ANSIBLE_TIMEOUT', 5),
];

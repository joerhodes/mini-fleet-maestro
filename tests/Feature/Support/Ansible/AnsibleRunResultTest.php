<?php

use App\Enums\AnsibleRunOutcome;
use App\Support\Ansible\AnsibleRunResult;

test('maps the exit code to an outcome', function (int $exitCode, AnsibleRunOutcome $expected) {
    $result = new AnsibleRunResult(exitCode: $exitCode, output: '', errorOutput: '');

    expect($result->outcome())->toBe($expected);
})->with([
    'success' => [0, AnsibleRunOutcome::Success],
    'host failed' => [2, AnsibleRunOutcome::HostFailed],
    'unreachable' => [4, AnsibleRunOutcome::Unreachable],
    'generic error' => [1, AnsibleRunOutcome::Error],
    'unmapped code' => [99, AnsibleRunOutcome::Error],
]);

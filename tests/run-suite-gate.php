<?php

/**
 * Gate runner: default order x2, seeded shuffle x2. PHP 5.6 compatible.
 */

$phar = '/tmp/phpunit-4.8.36.phar';
if (!is_file($phar)) {
    fwrite(STDERR, "Missing PHPUnit PHAR at {$phar}; run tests/bin/ensure-phpunit-phar.sh first.\n");
    exit(1);
}

$workspace = dirname(__DIR__);
$testFiles = glob($workspace . '/tests/*Test.php');
sort($testFiles, SORT_STRING);

if (count($testFiles) === 0) {
    fwrite(STDERR, "No test files found.\n");
    exit(1);
}

function runPhpunit($phar, $workspace, $files = null)
{
    $cmd = 'php ' . escapeshellarg($phar)
        . ' --bootstrap ' . escapeshellarg($workspace . '/tests/bootstrap.php')
        . ' --colors=never';

    if ($files === null) {
        $cmd .= ' ' . escapeshellarg($workspace . '/tests');
    } else {
        foreach ($files as $file) {
            $cmd .= ' ' . escapeshellarg($file);
        }
    }

    passthru($cmd, $exitCode);

    return (int) $exitCode;
}

$failures = 0;

fwrite(STDOUT, "=== Gate run 1/4: default order ===\n");
if (runPhpunit($phar, $workspace) !== 0) {
    ++$failures;
}

fwrite(STDOUT, "=== Gate run 2/4: default order ===\n");
if (runPhpunit($phar, $workspace) !== 0) {
    ++$failures;
}

$shuffled = $testFiles;
srand(20261008);
shuffle($shuffled);

function runPhpunitFileOrder($phar, $workspace, array $files)
{
    foreach ($files as $file) {
        if (runPhpunit($phar, $workspace, array($file)) !== 0) {
            return 1;
        }
    }

    return 0;
}

fwrite(STDOUT, "=== Gate run 3/4: seeded shuffle ===\n");
if (runPhpunitFileOrder($phar, $workspace, $shuffled) !== 0) {
    ++$failures;
}

fwrite(STDOUT, "=== Gate run 4/4: seeded shuffle ===\n");
if (runPhpunitFileOrder($phar, $workspace, $shuffled) !== 0) {
    ++$failures;
}

if ($failures > 0) {
    fwrite(STDERR, "Gate failed on {$failures} of 4 runs.\n");
    exit(1);
}

fwrite(STDOUT, "Gate passed all 4 runs.\n");
exit(0);

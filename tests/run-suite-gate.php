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
$expectedTestCount = 21;
$expectedTestFileNames = array(
    'tests/ExitGuardTest.php',
    'tests/PsDataprivacyConfigFormTest.php',
    'tests/PsDataprivacyGetContentTest.php',
    'tests/PsDataprivacyHookTest.php',
    'tests/PsDataprivacyInstallTest.php',
    'tests/PsDataprivacyMetadataTest.php',
    'tests/UpgradeModule210Test.php',
);
sort($expectedTestFileNames, SORT_STRING);

$expectedTestFiles = array();
foreach ($expectedTestFileNames as $relativePath) {
    $expectedTestFiles[] = $workspace . '/' . $relativePath;
}

$discoveredTestFiles = glob($workspace . '/tests/*Test.php');
sort($discoveredTestFiles, SORT_STRING);

if ($discoveredTestFiles !== $expectedTestFiles) {
    fwrite(STDERR, "Discovered test files do not match the pinned gate list.\n");
    fwrite(STDERR, 'Expected: ' . implode(', ', $expectedTestFileNames) . "\n");
    fwrite(STDERR, 'Found: ' . implode(', ', array_map('basename', $discoveredTestFiles)) . "\n");
    exit(1);
}

$testFiles = $expectedTestFiles;

function runPhpunitCapture($phar, $workspace, $files = null)
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

    $output = array();
    $exitCode = 0;
    exec($cmd . ' 2>&1', $output, $exitCode);
    $text = implode("\n", $output);

    return array(
        'exit' => (int) $exitCode,
        'output' => $text,
        'tests' => parsePhpunitTestCount($text),
    );
}

function parsePhpunitTestCount($output)
{
    if (preg_match('/OK \((\d+) tests?,/', $output, $matches)) {
        return (int) $matches[1];
    }
    if (preg_match('/Tests: (\d+),/', $output, $matches)) {
        return (int) $matches[1];
    }

    return null;
}

function assertExpectedTestCount($label, $actual, $expected, &$failures)
{
    if ($actual === null) {
        fwrite(STDERR, "Could not parse test count for {$label}.\n");
        ++$failures;

        return false;
    }
    if ($actual !== $expected) {
        fwrite(STDERR, "{$label} test count {$actual} != {$expected}\n");
        ++$failures;

        return false;
    }

    fwrite(STDOUT, "{$label} test count: {$actual}\n");

    return true;
}

function runPhpunitFileOrder($phar, $workspace, array $files)
{
    $totalTests = 0;
    foreach ($files as $file) {
        $result = runPhpunitCapture($phar, $workspace, array($file));
        if ($result['exit'] !== 0) {
            fwrite(STDERR, $result['output'] . "\n");

            return array('exit' => 1, 'tests' => null);
        }
        if ($result['tests'] === null) {
            fwrite(STDERR, "Could not parse test count for {$file}\n");

            return array('exit' => 1, 'tests' => null);
        }
        $totalTests += $result['tests'];
    }

    return array('exit' => 0, 'tests' => $totalTests);
}

function seededFileOrder(array $sortedFiles)
{
    $shuffled = $sortedFiles;
    srand(20261008);
    shuffle($shuffled);

    return $shuffled;
}

$failures = 0;

fwrite(STDOUT, "=== Gate run 1/4: default order ===\n");
$run1 = runPhpunitCapture($phar, $workspace);
if ($run1['exit'] !== 0) {
    fwrite(STDERR, $run1['output'] . "\n");
    ++$failures;
} else {
    assertExpectedTestCount('Default run 1', $run1['tests'], $expectedTestCount, $failures);
}

fwrite(STDOUT, "=== Gate run 2/4: default order ===\n");
$run2 = runPhpunitCapture($phar, $workspace);
if ($run2['exit'] !== 0) {
    fwrite(STDERR, $run2['output'] . "\n");
    ++$failures;
} else {
    assertExpectedTestCount('Default run 2', $run2['tests'], $expectedTestCount, $failures);
}

for ($seededRun = 3; $seededRun <= 4; ++$seededRun) {
    fwrite(STDOUT, "=== Gate run {$seededRun}/4: seeded shuffle ===\n");
    $shuffled = seededFileOrder($testFiles);
    if (count($shuffled) !== count($expectedTestFileNames)) {
        fwrite(STDERR, "Seeded file list length mismatch.\n");
        ++$failures;
        continue;
    }
    $result = runPhpunitFileOrder($phar, $workspace, $shuffled);
    if ($result['exit'] !== 0) {
        ++$failures;
        continue;
    }
    assertExpectedTestCount("Seeded run {$seededRun}", $result['tests'], $expectedTestCount, $failures);
}

if ($failures > 0) {
    fwrite(STDERR, "Gate failed on {$failures} issue(s).\n");
    exit(1);
}

fwrite(STDOUT, "Gate passed all 4 runs ({$expectedTestCount} tests each).\n");
exit(0);

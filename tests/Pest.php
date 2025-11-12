<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Monorepo Test Configuration
|--------------------------------------------------------------------------
|
| This is the root-level Pest configuration that orchestrates test execution
| across all apps and packages in the monorepo. Each app/package has its own
| test suite that can be run independently or as part of the full suite.
|
*/

use Pest\TestSuite;

// Configure Pest to discover tests in apps and packages
pest()->extend(Tests\TestCase::class)->in('.');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| Global expectations that are available in all test files.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| Global helper functions for testing.
|
*/

/**
 * Get the path to a specific app's test directory
 */
function appTestPath(string $app): string
{
    return __DIR__."/../apps/{$app}/tests";
}

/**
 * Get the path to a specific package's test directory
 */
function packageTestPath(string $package): string
{
    return __DIR__."/../packages/{$package}/tests";
}

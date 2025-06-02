<?php
/**
 * Application Installer Script
 *
 * This script helps set up environment variables for PHP applications,
 * with special support for Laravel applications.
 *
 * It creates or updates the .env file with user-provided configuration values.
 */
function ask(string $question, string $default = ''): string
{
    $answer = readline($question . ($default ? " ({$default})" : null) . ': ');

    if (!$answer) {
        return $default;
    }

    return $answer;
}

function ask_password(string $question): string
{
    echo $question;
    shell_exec('stty -echo');
    $pass = trim(fgets(STDIN));
    shell_exec('stty echo');
    echo PHP_EOL; // Add a newline after password input
    return $pass;
}

function confirm(string $question, bool $default = false): bool
{
    $answer = ask($question . ' (' . ($default ? 'Y/n' : 'y/N') . ')');

    if (!$answer) {
        return $default;
    }

    return strtolower($answer) === 'y';
}

function writeln(string $line): void
{
    echo $line . PHP_EOL;
}

function run(string $command): string
{
    return trim((string)shell_exec($command));
}

function str_after(string $subject, string $search): string
{
    $pos = strrpos($subject, $search);

    if ($pos === false) {
        return $subject;
    }

    return substr($subject, $pos + strlen($search));
}

function checkIfHasEnvExampleExist(): bool
{
    return file_exists(__DIR__ . '/.env.example');
}

function checkIfEnvExist(): bool
{
    return file_exists(__DIR__ . '/.env');
}

/**
 * Check if Laravel Reverb is installed by examining composer.json
 *
 * @return bool True if Laravel Reverb is installed, false otherwise
 */
function isReverbInstalled(): bool
{
    $composerJsonPath = __DIR__ . '/composer.json';

    // Check if composer.json exists
    if (!file_exists($composerJsonPath)) {
        return false;
    }

    // Read and parse composer.json
    $composerContent = file_get_contents($composerJsonPath);
    $composerData = json_decode($composerContent, true);

    // Check if parsing was successful
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($composerData)) {
        return false;
    }

    // Check if Laravel Reverb is in require or require-dev sections
    if (isset($composerData['require']) && isset($composerData['require']['laravel/reverb'])) {
        return true;
    }

    if (isset($composerData['require-dev']) && isset($composerData['require-dev']['laravel/reverb'])) {
        return true;
    }

    return false;
}

/**
 * Generate a random string of specified length
 *
 * @param int $length The length of the random string to generate
 * @return string The generated random string
 */
function generateRandomString(int $length = 32): string
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }

    return $randomString;
}

/**
 * Get a value from the .env file
 *
 * @param string $key The key to look for in the .env file
 * @param string|null $default The default value to return if the key is not found
 * @param string|null $envPath Optional path to the .env file (defaults to project root)
 * @return string|null The value of the key if found, or the default value if not found
 */
function getEnvValue(string $key, ?string $default = null, ?string $envPath = null): ?string
{
    // Determine the .env file path
    $envPath = $envPath ?? __DIR__ . '/.env';

    // Check if .env file exists
    if (!file_exists($envPath)) {
        return $default;
    }

    // Read the .env file content
    $envContent = file_get_contents($envPath);

    // Look for the key in the file
    $pattern = "/^{$key}=(.*)$/m";

    if (preg_match($pattern, $envContent, $matches)) {
        $value = $matches[1];

        // Remove quotes if present
        if (preg_match('/^"(.*)"$/', $value, $quotedMatches)) {
            $value = $quotedMatches[1];
        } elseif (preg_match("/^'(.*)'$/", $value, $quotedMatches)) {
            $value = $quotedMatches[1];
        }

        return $value;
    }

    return $default;
}

/**
 * Update or add a value in the .env file
 *
 * @param string $key The key to update or add in the .env file
 * @param string $value The new value to set
 * @param string|null $envPath Optional path to the .env file (defaults to project root)
 * @return bool True if the update was successful, false otherwise
 */
function updateEnvValue(string $key, string $value, ?string $envPath = null): bool
{
    // Determine the .env file path
    $envPath = $envPath ?? __DIR__ . '/.env';

    // Check if .env file exists, create it if it doesn't
    if (!file_exists($envPath)) {
        if (!touch($envPath)) {
            return false;
        }
    }

    // Read the .env file content
    $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';

    // Check if the value needs to be quoted
    if (strpos($value, ' ') !== false || strpos($value, '#') !== false) {
        $value = '"' . str_replace('"', '\"', $value) . '"';
    }

    // Check if the key already exists in the file
    $pattern = "/^{$key}=.*$/m";

    if (preg_match($pattern, $envContent)) {
        // Update existing key
        $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
    } else {
        // Add new key
        $envContent .= PHP_EOL . "{$key}={$value}";
    }

    // Write the updated content back to the file
    return file_put_contents($envPath, $envContent) !== false;
}


if (!checkIfEnvExist()) {
    writeln('No .env file found.');
    if (checkIfHasEnvExampleExist()) {
        writeln('Creating .env file from .env.example...');
        if (copy(__DIR__ . '/.env.example', __DIR__ . '/.env')) {
            writeln('.env file created successfully from .env.example!');
        }
    } else {
        writeln('No .env.example file found. Creating a basic .env file...');
    }
}

if (!checkIfEnvExist()) {
    writeln('No .env file found. Skipping environment configuration.');
}


$appName = ask('Enter Application Name: ', getEnvValue('APP_NAME', 'Laravel'));
$appEnv = ask('Enter Application Environment: ', getEnvValue('APP_ENV', 'production'));
$appUrl = ask('Enter Application URL: ', getEnvValue('APP_URL', 'http://localhost'));
$appDebug = $appEnv !== 'production' ? 'true' : 'false';
$appTimezone = ask('Enter Application Timezone: ', getEnvValue('APP_TIMEZONE', 'UTC'));

$logChannel = ask('Enter Log Channel: ', getEnvValue('LOG_CHANNEL', 'single'));
$logStack = ask('Enter Log Stack: ', getEnvValue('LOG_STACK', 'daily'));
$logLevel = ask('Enter Log Level: ', getEnvValue('LOG_LEVEL', 'debug'));;

$dbConnection = ask('Enter Database Connection Name: ', getEnvValue('DB_CONNECTION', 'mysql'));
$dbHost = $dbConnection === 'sqlite' ? 'N/A' : ask('Enter Database Host: ', getEnvValue('DB_HOST', '127.0.0.1'));
$dbPort = $dbConnection === 'sqlite' ? 'N/A' : ask('Enter Database Port: ', $dbConnection === 'pgsql' ? '5432' : ($dbConnection === 'sqlsrv' ? '1433' : '3306'));
$dbName = $dbConnection === 'sqlite' ? 'N/A' : ask('Enter Database Name: ', getEnvValue('DB_DATABASE', 'laravel'));;
$dbUsername = $dbConnection === 'sqlite' ? 'N/A' : ask('Enter Database Username: ', getEnvValue('DB_USERNAME', 'root'));
$dbPassword = $dbConnection === 'sqlite' ? 'N/A' : ask_password('Enter Database Password: ');

if (isReverbInstalled()) {
    $reverbAppId = ask('Enter Reverb App ID: ', getEnvValue('REVERB_APP_ID', random_int(100_000, 999_999)));
    $reverbAppKey = ask('Enter Reverb App Key: ', getEnvValue('REVERB_APP_KEY', generateRandomString(20)));
    $reverbAppSecret = ask('Enter Reverb App Secret: ', getEnvValue('REVERB_APP_SECRET', generateRandomString(20)));
}


writeln('------');
writeln('Application Name         : ' . $appName);
writeln('Application Environment  : ' . $appEnv);
writeln('Application URL          : ' . $appUrl);
writeln('Application Debug        : ' . $appDebug);
writeln('Application Timezone     : ' . $appTimezone);
writeln('------');
writeln('Log Channel              : ' . $logChannel);
writeln('Log Stack                : ' . $logStack);
writeln('Log Level                : ' . $logLevel);
writeln('------');
writeln('Database Connection      : ' . $dbConnection);
writeln('Database Host            : ' . $dbHost);
writeln('Database Port            : ' . $dbPort);
writeln('Database Name            : ' . $dbName);
writeln('Database Username        : ' . $dbUsername);
writeln('Database Password        : *********');
writeln('------');
if (isReverbInstalled()) {
    writeln('Reverb App ID            : ' . $reverbAppId);
    writeln('Reverb App Key           : ' . $reverbAppKey);
    writeln('Reverb App Secret        : ' . $reverbAppSecret);
    writeln('------');
}

writeln('This script will replace the above values in all relevant variables in the project environment files.');

if (!confirm('Modify files?', true)) {
    exit(1);
}

writeln('Updating .env file...');

updateEnvValue('APP_NAME', $appName);
updateEnvValue('APP_ENV', $appEnv);
updateEnvValue('APP_URL', $appUrl);
updateEnvValue('APP_DEBUG', $appDebug);
updateEnvValue('APP_TIMEZONE', $appTimezone);

updateEnvValue('LOG_CHANNEL', $logChannel);
updateEnvValue('LOG_STACK', $logStack);
updateEnvValue('LOG_LEVEL', $logLevel);

updateEnvValue('DB_CONNECTION', $dbConnection);
if($dbConnection && strtolower($dbConnection) === 'mysql') {
    updateEnvValue('DB_HOST', $dbHost);
    updateEnvValue('DB_PORT', $dbPort);
    updateEnvValue('DB_DATABASE', $dbName);
    updateEnvValue('DB_USERNAME', $dbUsername);
    updateEnvValue('DB_PASSWORD', $dbPassword);
}

if (isReverbInstalled()) {
    updateEnvValue('REVERB_APP_ID', $reverbAppId);
    updateEnvValue('REVERB_APP_KEY', $reverbAppKey);
    updateEnvValue('REVERB_APP_SECRET', $reverbAppSecret);
}

writeln('.env file has been updated!');


confirm('Execute `npm install`?') && run('npm install');
confirm('Execute `composer install --no-dev`?', true)
    ? run('composer install --no-dev --no-interaction')
    : run('composer install --no-interaction');

run('php artisan key:generate');
run('php artisan storage:unlink'); // refresh the symlink
run('php artisan storage:link');
run('php artisan migrate --force --no-interaction');


// place here your additional call or DB Seeders


confirm('Let this script delete itself?', true) && unlink(__FILE__);

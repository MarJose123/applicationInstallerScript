#!/usr/bin/env php
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

function slugify(string $subject): string
{
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $subject), '-'));
}

function replace_in_file(string $file, array $replacements): void
{
    $contents = file_get_contents($file);

    file_put_contents($file, str_replace(array_keys($replacements), array_values($replacements), $contents));
}

function determineSeparator(string $path): string
{
    return str_replace('/', DIRECTORY_SEPARATOR, $path);
}

function isComposerFileExists(): bool
{
    return file_exists(__DIR__ . '/composer.json');
}

function checkComposerIfLaravelIsInstalled(): bool
{
    if (!isComposerFileExists()) {
        return false;
    }
    $data = json_decode(file_get_contents(__DIR__ . '/composer.json'), true);
    foreach ($data['require'] as $name => $package) {
        if ('laravel/framework' === $name) {
            return true;
        }
    }
    return false;
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
 * Create a basic .env file with optional initial content
 *
 * @param string $initialContent The initial content to write to the .env file
 * @return bool True if successful, false otherwise
 */
function createBasicEnvFile(string $initialContent = "# Environment Configuration\n\n"): bool
{
    $result = file_put_contents(__DIR__ . '/.env', $initialContent);
    return $result !== false;
}

/**
 * Configure a section of environment variables with user input
 *
 * @param string $sectionTitle The title of the configuration section
 * @param array $configItems Array of configuration items with keys:
 *                          - 'key': The env variable key
 *                          - 'question': The question to ask the user
 *                          - 'default': The default value (optional)
 *                          - 'isPassword': Whether this is a password field (optional, default false)
 *                          - 'quote': Whether to quote the value in .env (optional, default false)
 * @return array The collected configuration values
 */
function configureEnvSection(string $sectionTitle, array $configItems): array
{
    writeln('');
    writeln("Setting up {$sectionTitle}...");
    writeln('');

    $values = [];
    $confirmed = false;

    do {
        foreach ($configItems as $item) {
            $key = $item['key'];
            $question = $item['question'];
            $default = $item['default'] ?? '';
            $isPassword = $item['isPassword'] ?? false;

            if ($isPassword) {
                $values[$key] = ask_password($question);
            } else {
                $values[$key] = ask($question, $default);
            }
        }

        $confirmed = confirm('Are all the above settings correct?');
    } while (!$confirmed);

    writeln("Updating {$sectionTitle} in .env file...");

    // Update .env file with collected values
    foreach ($configItems as $item) {
        $key = $item['key'];
        $quote = $item['quote'] ?? false;
        $value = $values[$key];

        if ($quote) {
            $value = '"' . $value . '"';
        }

        updateEnvVariable($key, $value);
    }

    writeln("{$sectionTitle} updated successfully!");

    return $values;
}

/**
 * Check and update a variable value in the .env file
 *
 * @param string $key The key to check/update
 * @param string $value The value to set
 * @param string|null $envPath Optional path to the .env file (defaults to project root)
 * @return bool True if successful, false otherwise
 */
function updateEnvVariable(string $key, string $value, ?string $envPath = null): bool
{
    // Determine the .env file path
    $envPath = $envPath ?? __DIR__ . '/.env';

    // Check if .env file exists
    if (!checkIfEnvExist()) {
        return false;
    }

    // Read the .env file content
    $envContent = file_get_contents($envPath);

    // Prepare the key-value pair in the format KEY=VALUE
    $keyValuePair = "{$key}={$value}";

    // Check if the key already exists in the file
    $pattern = "/^{$key}=.*/m";

    if (preg_match($pattern, $envContent)) {
        // Key exists, update its value
        $envContent = preg_replace($pattern, $keyValuePair, $envContent);
    } else {
        // Key doesn't exist, add it to the end of the file
        // Make sure there's a newline at the end of the file
        if (substr($envContent, -1) !== "\n") {
            $envContent .= "\n";
        }
        $envContent .= "{$keyValuePair}\n";
    }

    // Write the updated content back to the .env file
    return file_put_contents($envPath, $envContent) !== false;
}


/**
 * ==========================================================================
 *
 *  Installer Main Logic
 *
 * ==========================================================================
 *
 * The script follows these main steps:
 * 1. Check if composer.json exists and if Laravel is installed
 * 2. Create or update the .env file
 * 3. Configure environment variables based on the application type (Laravel or vanilla PHP)
 * 4. Run composer install
 */
writeln('------------------');

/**
 * General
 */
$composerExists = isComposerFileExists();
$laravelInstalled = checkComposerIfLaravelIsInstalled();


/**
 * Laravel Specific
 */

if ($laravelInstalled) {

// Check if .env file exists, if not create it from .env.example
    if (!checkIfEnvExist()) {
        writeln('No .env file found.');
        if (checkIfHasEnvExampleExist()) {
            writeln('Creating .env file from .env.example...');
            if (copy(__DIR__ . '/.env.example', __DIR__ . '/.env')) {
                writeln('.env file created successfully from .env.example!');
            } else {
                writeln('Failed to create .env file from .env.example. Creating a basic .env file...');
                if (createBasicEnvFile("# Laravel Environment Configuration\n\n")) {
                    writeln('Basic .env file created successfully!');
                } else {
                    writeln('Failed to create basic .env file!');
                }
            }
        } else {
            writeln('No .env.example file found. Creating a basic .env file...');
            if (createBasicEnvFile("# Laravel Environment Configuration\n\n")) {
                writeln('Basic .env file created successfully!');
            } else {
                writeln('Failed to create basic .env file!');
            }
        }
    }

    writeln('Web App is using Laravel.');

    // Application configuration
    $appConfigItems = [['key' => 'APP_NAME', 'question' => 'Enter Application Name: ', 'default' => 'Laravel', 'quote' => true // Quote to handle spaces in app name
    ], ['key' => 'APP_ENV', 'question' => 'Enter Application Environment (local/production/staging): ', 'default' => 'local'], ['key' => 'APP_URL', 'question' => 'Enter Application URL: ', 'default' => 'http://localhost']];

    $appValues = configureEnvSection('Laravel application environment', $appConfigItems);

    // Set APP_DEBUG based on environment
    $appDebug = $appValues['APP_ENV'] !== 'production' ? 'true' : 'false';
    updateEnvVariable('APP_DEBUG', $appDebug);

    // Handle APP_KEY generation if needed
    if (confirm('Would you like to generate a new APP_KEY?', false)) {
        writeln('Generating new APP_KEY...');
        // Generate key manually since vendor is not installed yet
        $appKey = 'base64:' . base64_encode(random_bytes(32));
        updateEnvVariable('APP_KEY', $appKey);
        writeln('Generated APP_KEY and set it in .env file.');
    }

    writeln('Application configuration updated successfully!');
    writeln('');

    // Database configuration
    // First, ask for the database connection type
    $dbConnectionItem = [['key' => 'DB_CONNECTION', 'question' => 'Enter Database Connection Name (mysql/pgsql/sqlite/sqlsrv): ', 'default' => 'mysql']];

    $dbConnectionValues = configureEnvSection('Laravel Database Connection', $dbConnectionItem);
    $dbConnection = $dbConnectionValues['DB_CONNECTION'];

    // Define database configuration items based on connection type
    if ($dbConnection === 'sqlite') {
        // For SQLite, we only need the database path
        $dbConfigItems = [['key' => 'DB_DATABASE', 'question' => 'Enter SQLite Database Path (relative to database directory): ', 'default' => '']];

        writeln('Using SQLite database - host, port, username and password configuration skipped.');
    } else {
        // For other database types, we need all configuration items
        $dbConfigItems = [['key' => 'DB_HOST', 'question' => 'Enter Database Host: ', 'default' => '127.0.0.1'], ['key' => 'DB_PORT', 'question' => 'Enter Database Port: ', 'default' => $dbConnection === 'pgsql' ? '5432' : ($dbConnection === 'sqlsrv' ? '1433' : '3306')], ['key' => 'DB_DATABASE', 'question' => 'Enter Database Name: ', 'default' => 'laravel'], ['key' => 'DB_USERNAME', 'question' => 'Enter Database Username: ', 'default' => 'root'], ['key' => 'DB_PASSWORD', 'question' => 'Enter Database Password: ', 'isPassword' => true]];
    }

    configureEnvSection('Laravel Database Configuration', $dbConfigItems);

    writeln('Database configuration updated successfully!');

    // Mail configuration
    if (confirm('Would you like to configure mail settings?', false)) {
        $mailConfigItems = [['key' => 'MAIL_MAILER', 'question' => 'Enter Mail Mailer (smtp/sendmail/mailgun/etc): ', 'default' => 'smtp'], ['key' => 'MAIL_HOST', 'question' => 'Enter Mail Host: ', 'default' => 'smtp.mailtrap.io'], ['key' => 'MAIL_PORT', 'question' => 'Enter Mail Port: ', 'default' => '2525'], ['key' => 'MAIL_USERNAME', 'question' => 'Enter Mail Username: ', 'default' => ''], ['key' => 'MAIL_PASSWORD', 'question' => 'Enter Mail Password: ', 'isPassword' => true], ['key' => 'MAIL_ENCRYPTION', 'question' => 'Enter Mail Encryption (tls/ssl/null): ', 'default' => 'tls'], ['key' => 'MAIL_FROM_ADDRESS', 'question' => 'Enter Mail From Address: ', 'default' => 'hello@example.com'], ['key' => 'MAIL_FROM_NAME', 'question' => 'Enter Mail From Name: ', 'default' => $appValues['APP_NAME'] ?? 'Laravel', 'quote' => true]];

        configureEnvSection('Laravel Mail configuration', $mailConfigItems);
    }

    // Cache and Session configuration
    if (confirm('Would you like to configure cache and session settings?', false)) {
        // First, configure cache driver
        $cacheConfigItems = [['key' => 'CACHE_DRIVER', 'question' => 'Enter Cache Driver (file/redis/memcached/database/array): ', 'default' => 'file']];

        $cacheValues = configureEnvSection('Laravel Cache configuration', $cacheConfigItems);

        // If Redis is selected, configure Redis settings
        if ($cacheValues['CACHE_DRIVER'] === 'redis') {
            $redisConfigItems = [['key' => 'REDIS_HOST', 'question' => 'Enter Redis Host: ', 'default' => '127.0.0.1'], ['key' => 'REDIS_PASSWORD', 'question' => 'Enter Redis Password (leave empty if none): ', 'isPassword' => true], ['key' => 'REDIS_PORT', 'question' => 'Enter Redis Port: ', 'default' => '6379']];

            $redisValues = configureEnvSection('Redis configuration', $redisConfigItems);

            // Handle empty Redis password
            if (empty($redisValues['REDIS_PASSWORD'])) {
                updateEnvVariable('REDIS_PASSWORD', 'null');
            }
        }

        // Now configure session settings
        $sessionConfigItems = [['key' => 'SESSION_DRIVER', 'question' => 'Enter Session Driver (file/cookie/database/redis/memcached/array): ', 'default' => 'file'], ['key' => 'SESSION_LIFETIME', 'question' => 'Enter Session Lifetime (in minutes): ', 'default' => '120']];

        configureEnvSection('Laravel Session configuration', $sessionConfigItems);
    }

    // Queue configuration
    if (confirm('Would you like to configure queue settings?', false)) {
        // First, configure queue connection
        $queueConfigItems = [['key' => 'QUEUE_CONNECTION', 'question' => 'Enter Queue Connection (sync/database/redis/beanstalkd/sqs): ', 'default' => 'sync']];

        $queueValues = configureEnvSection('Laravel Queue configuration', $queueConfigItems);

        // If not using sync driver, configure worker settings
        if ($queueValues['QUEUE_CONNECTION'] !== 'sync') {
            $queueWorkerConfigItems = [['key' => 'QUEUE_WORKER_TIMEOUT', 'question' => 'Enter Queue Worker Timeout (in seconds): ', 'default' => '60'], ['key' => 'QUEUE_WORKER_SLEEP', 'question' => 'Enter Queue Worker Sleep (in seconds): ', 'default' => '3'], ['key' => 'QUEUE_WORKER_TRIES', 'question' => 'Enter Queue Worker Tries: ', 'default' => '1']];

            configureEnvSection('Queue Worker configuration', $queueWorkerConfigItems);
        }
    }

    // AWS S3 Storage configuration
    if (confirm('Would you like to configure AWS S3 storage?', false)) {
        $awsConfigItems = [['key' => 'AWS_ACCESS_KEY_ID', 'question' => 'Enter AWS Access Key ID: ', 'default' => ''], ['key' => 'AWS_SECRET_ACCESS_KEY', 'question' => 'Enter AWS Secret Access Key: ', 'isPassword' => true], ['key' => 'AWS_DEFAULT_REGION', 'question' => 'Enter AWS Default Region: ', 'default' => 'us-east-1'], ['key' => 'AWS_BUCKET', 'question' => 'Enter AWS S3 Bucket Name: ', 'default' => ''], ['key' => 'AWS_URL', 'question' => 'Enter AWS URL (optional): ', 'default' => ''], ['key' => 'AWS_ENDPOINT', 'question' => 'Enter AWS Endpoint (optional, for non-AWS S3 compatible services): ', 'default' => ''], ['key' => 'AWS_USE_PATH_STYLE_ENDPOINT', 'question' => 'Use path-style endpoint? (true/false): ', 'default' => 'false']];

        $awsValues = configureEnvSection('AWS S3 Storage configuration', $awsConfigItems);

        // Update filesystem disk to s3
        updateEnvVariable('FILESYSTEM_DISK', 's3');
        writeln('Filesystem disk set to s3.');
    }

    // Additional deployment-specific settings
    if (confirm('Would you like to configure additional deployment settings?', false)) {
        // Logging configuration
        $loggingConfigItems = [['key' => 'LOG_CHANNEL', 'question' => 'Enter Log Channel (stack/single/daily/slack/stderr): ', 'default' => 'stack'], ['key' => 'LOG_LEVEL', 'question' => 'Enter Log Level (debug/info/notice/warning/error/critical/alert/emergency): ', 'default' => 'debug']];

        configureEnvSection('Logging configuration', $loggingConfigItems);

        // CORS configuration
        $corsConfigItems = [['key' => 'CORS_ALLOWED_ORIGINS', 'question' => 'Enter CORS Allowed Origins (comma-separated, * for all): ', 'default' => '*']];

        configureEnvSection('CORS configuration', $corsConfigItems);

        // Trusted proxies configuration
        $proxyConfigItems = [['key' => 'TRUSTED_PROXIES', 'question' => 'Enter Trusted Proxies (comma-separated, * for all): ', 'default' => '*']];

        configureEnvSection('Trusted Proxies configuration', $proxyConfigItems);

        // Force HTTPS in production
        if (confirm('Force HTTPS in production?', true)) {
            updateEnvVariable('FORCE_HTTPS', 'true');
            writeln('HTTPS will be forced in production.');
        }
    }

    writeln('');
    writeln('Laravel environment configuration completed successfully!');
} /**
 * Vanilla PHP Specific
 */ else {
    writeln('Web App is using vanilla PHP.');

    // Check if .env file exists, if not, create it
    if (!checkIfEnvExist()) {
        writeln('No .env file found. Creating one...');
        if (createBasicEnvFile()) {
            writeln('.env file created successfully!');
        } else {
            writeln('Failed to create .env file!');
        }
    }

    writeln('Setting up environment variables...');

    // Application configuration
    $appConfigItems = [['key' => 'APP_NAME', 'question' => 'Enter Application Name: ', 'default' => 'MyApp'], ['key' => 'APP_ENV', 'question' => 'Enter Environment (production/development): ', 'default' => 'development']];

    $appValues = configureEnvSection('Application configuration', $appConfigItems);

    // Set APP_DEBUG based on environment
    $appDebug = $appValues['APP_ENV'] === 'development' ? 'true' : 'false';
    updateEnvVariable('APP_DEBUG', $appDebug);
    writeln('APP_DEBUG set to ' . $appDebug . ' based on environment.');

    // Database configuration
    $dbConfigItems = [['key' => 'DB_HOST', 'question' => 'Enter Database Host: ', 'default' => 'localhost'], ['key' => 'DB_NAME', 'question' => 'Enter Database Name: ', 'default' => 'myapp'], ['key' => 'DB_USER', 'question' => 'Enter Database Username: ', 'default' => 'root'], ['key' => 'DB_PASS', 'question' => 'Enter Database Password: ', 'isPassword' => true]];

    configureEnvSection('Database configuration', $dbConfigItems);

    writeln('Environment variables updated successfully!');
}

writeln('');

// Check if package.json exists to determine if npm is needed
$packageJsonExists = file_exists(__DIR__ . '/package.json');

// Run npm install if package.json exists and the user confirms
if ($packageJsonExists && confirm('Would you like to run npm install for frontend dependencies?', true)) {
    $node = ask('Package Manager (npm/yarn/pnpm):', 'npm');
    writeln("Running {$node} install...");
    $npmInstallOutput = run("{$node} install");
    writeln("{$node} install completed.");
    writeln('');
}

$composerInstallWithoutDev = confirm('Would you like to run composer install without dev dependencies?', true);
writeln('Running composer install...');
if ($composerInstallWithoutDev) {
    run('composer install --no-dev --no-interaction');
} else {
    run('composer install --no-interaction');
}


writeln('');
writeln('Installation completed successfully!');

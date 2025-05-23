# Application Installer Script

![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-All%20Versions-red?logo=laravel&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-yellow?logo=license&logoColor=white)

## Introduction
This repository contains a PHP-based application installer script designed to automate the installation and configuration process of applications. The script provides a set of utility functions to interact with users through the command line, manipulate files, and execute shell commands.

## Purpose
The Application Installer Script simplifies the installation process by:
- Providing interactive prompts for configuration options
- Handling user input securely (including password input)
- Automating file modifications
- Executing necessary shell commands
- Supporting cross-platform path handling

This tool is particularly useful for developers who need to distribute applications that require a setup process or for automating repetitive installation tasks.

## Supported Frameworks
Currently, the Application Installer Script supports the following frameworks:
- [x] **PHP** - Compatible with PHP 8.0 or higher
- [x] **Laravel** - Fully compatible with all Laravel versions
- [ ] **Other Framework** - _Submit PR_

## How to Execute the Install Script

### Prerequisites
- PHP 8.0 or higher installed on your system
- Command-line access

### Execution Instructions

Make sure to copy only the `setup-x.php` file (not the entire folder) to your web application's root directory.

#### On Linux/macOS:
1. Open a terminal

2. Copy the `setup-x.php` file to your web application's root directory:
   ```
   cp path/to/applicationInstallerScript/setup-x.php /path/to/your/web/app/
   ```

3. Navigate to your web application directory:
   ```
   cd /path/to/your/web/app
   ```

4. Make the script executable (if not already):
   ```
   chmod +x setup-x.php
   ```

5. Run the script:
   ```
   ./setup-x.php
   ```
   Alternatively, you can use:
   ```
   php ./setup-x.php
   ```
   Or:
   ```
   php setup-x.php
   ```

#### On Windows:
1. Open Command Prompt or PowerShell

2. Copy the `setup-x.php` file to your web application's root directory:
   ```
   copy path\to\applicationInstallerScript\setup-x.php C:\path\to\your\web\app\
   ```

3. Navigate to your web application directory:
   ```
   cd C:\path\to\your\web\app
   ```

4. Run the script using PHP:
   ```
   php ./setup-x.php
   ```
   Or:
   ```
   php setup-x.php
   ```

#### For PHP/Laravel Applications Specifically
You can also use the installer with PHP/Laravel applications:

```
# On Linux/macOS
cp path/to/applicationInstallerScript/setup-laravel.php /path/to/your/laravel/app/
cd /path/to/your/laravel/app

# On Windows
copy path\to\applicationInstallerScript\setup-laravel.php C:\path\to\your\laravel\app\
cd C:\path\to\your\laravel\app
```

Then execute the script as described above.

### Notes
- The script will guide you through the installation process with interactive prompts
- Follow the on-screen instructions to complete the installation
- For any password prompts, your input will not be displayed on the screen for security reasons

## Customization
The script can be customized to fit specific installation requirements by modifying the main execution code. The existing utility functions provide a foundation for common installation tasks.

## Available Utility Functions

### Environment Variables
- `checkIfEnvExist()`: Checks if a .env file exists in the project root
- `checkIfHasEnvExampleExist()`: Checks if a .env.example file exists in the project root
- `getEnvValue(string $key, ?string $default = null, ?string $envPath = null)`: Gets a value from the .env file
  - `$key`: The key to look for in the .env file
  - `$default`: (Optional) The default value to return if the key is not found
  - `$envPath`: (Optional) Path to the .env file (defaults to project root)
  - Returns the value of the key if found, or the default value if not found

### User Interaction
- `ask(string $question, string $default = '')`: Prompts the user with a question and returns their answer
- `ask_password(string $question)`: Securely prompts the user for a password
- `confirm(string $question, bool $default = false)`: Asks the user a yes/no question
- `writeln(string $line)`: Outputs a line of text with a newline

### System Operations
- `run(string $command)`: Executes a shell command and returns the output
- `str_after(string $subject, string $search)`: Returns the portion of a string after a given value

## License
This project is licensed under the **MIT License** - see the [LICENSE](https://choosealicense.com/licenses/mit/) file for details.

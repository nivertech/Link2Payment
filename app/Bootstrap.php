<?php
/**
 * @author         Pierre-Henry Soria <hi@ph7.me>
 * @copyright      (c) 2017, Pierre-Henry Soria. All Rights Reserved.
 * @license        GNU General Public License; <https://www.gnu.org/licenses/gpl-3.0.en.html>
 */

declare(strict_types = 1);

namespace PH7App;

use Exception;
use PH7App\Core\Database;
use PH7App\Core\Main;

define('APP_PATH', __DIR__ . DIRECTORY_SEPARATOR);

require 'vendor/autoload.php';
require 'config/environment.php';
require 'helpers.php';

class Bootstrap
{
    private const DEFAULT_TIMEZONE = 'America/Chicago';

    public function __construct()
    {
        $this->autoloader();
    }

    public function run(): void
    {
        try {
            $dbDetails = [
                'dbHost' => getenv('DB_HOST'),
                'dbUser' => getenv('DB_USER'),
                'dbPass' => getenv('DB_PWD'),
                'dbName' => getenv('DB_NAME')
            ];
            Database::connect($dbDetails);

            require 'routes.php';
        } catch (Exception $except) {
            echo $except->getMessage();
        }
    }

    public function initializeDebugging(): void
    {
        // First, convert "true/false" string from phpdotenv to boolean
        $debugMode = filter_var(getenv('DEBUG_MODE'), FILTER_VALIDATE_BOOLEAN);

        if ($debugMode) {
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        } else {
            error_reporting(0);
            ini_set('display_errors', 'Off');
        }
    }

    /**
     * Check if the session is already initialized and initialize it if it isn't the case.
     *
     * @return void
     */
    public function initializePHPSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }

    /**
     * Set a default timezone if it is not already configured in environment.
     *
     * @return void
     */
    public function setTimezoneIfNotSet(): void
    {
        if (!ini_get('date.timezone')) {
            ini_set('date.timezone', self::DEFAULT_TIMEZONE);
        }
    }

    private function autoloader(): void
    {
        spl_autoload_register(function (string $className) {
            $className = str_replace([__NAMESPACE__ . '\\', '\\'], '/', $className);

            $filename = APP_PATH . $className . '.php';
            if (is_readable($filename)) {
                require $filename;
            }
        });
    }
}
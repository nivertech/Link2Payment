<?php
/**
 * @author         Pierre-Henry Soria <hi@ph7.me>
 * @copyright      (c) 2017, Pierre-Henry Soria. All Rights Reserved.
 * @license        GNU General Public License; <https://www.gnu.org/licenses/gpl-3.0.en.html>
 */

declare(strict_types = 1);

namespace PH7App\Core;

use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use PH7App\Controller\Base as BaseController;

class Route
{
    public const GET_METHOD = 'GET';
    public const POST_METHOD = 'POST';
    public const PUT_METHOD = 'PUT';
    public const DELETE_METHOD = 'DELETE';

    private const CONTROLLER_NAMESPACE = 'PH7App\Controller\\';
    private const SEPARATOR = '@';

    private static $httpMethod;

    public static function get(string $uri, string $classMethod = ''): void
    {
        self::$httpMethod = self::GET_METHOD;

        self::run($uri, $classMethod);
    }

    public static function post(string $uri, string $classMethod = ''): void
    {
        self::$httpMethod = self::POST_METHOD;
        self::run($uri, $classMethod);
    }

    public static function getAndPost(string $uri, string $classMethod = ''): void
    {
        self::$httpMethod = null;
        self::run($uri, $classMethod);
    }

    public static function location(string $fromUri, string $toUrl): void
    {
        self::run($fromUri, $toUrl);
    }

    public static function isHomePage(): bool
    {
        return empty($_GET['uri']);
    }

    public static function isStripePage(): bool
    {
        return !empty($_GET['uri']) && strpos($_GET['uri'], 'stripe') !== false;
    }

    /**
     * @param string $uri
     * @param string $value
     *
     * @return mixed
     *
     * @throws ReflectionException If the class or method doesn't exist.
     */
    private static function run(string $uri, string $value)
    {
        $uri = '/' . trim($uri, '/');
        $url = !empty($_GET['uri']) ? '/' . $_GET['uri'] : '/';

        if (preg_match("#^$uri$#", $url, $params)) {
            if (!self::isController($value)) {
                redirect($value);
            } else {
                if (!empty(self::$httpMethod) && $_SERVER['REQUEST_METHOD'] !== self::$httpMethod) {
                    //throw new InvalidArgumentException(sprintf('HTTP Method Must be %s', self::$httpMethod));
                    (new BaseController)->notFound();
                }

                $split = explode(self::SEPARATOR, $value);
                $className = self::CONTROLLER_NAMESPACE . $split[0];
                $method = $split[1];

                try {
                    if (class_exists($className) && (new ReflectionClass($className))->hasMethod($method)) {
                        $action = new ReflectionMethod($className, $method);
                        if ($action->isPublic()) {
                            // And finally we perform the controller's action
                            return $action->invokeArgs(new $className, self::getActionParameters($params));
                        }
                        unset($action);
                    }
                } catch (ReflectionException $except) {
                    (new BaseController)->notFound();
                }
            }
            //throw new RuntimeException('Method "' . $method . '" was not found in "' . $class . '" class.');
            (new BaseController)->notFound();
        }
    }

    private static function isController(string $method): bool
    {
        return strpos($method, self::SEPARATOR) !== false;
    }

    private static function getActionParameters(array $params): array
    {
        foreach ($params as $key => $val) {
            $params[$key] = str_replace('/', '', $val);
        }

        return $params;
    }
}

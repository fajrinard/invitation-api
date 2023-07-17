<?php

namespace Core\Routing;

use Core\Facades\App;

/**
 * Helper class untuk routing url.
 *
 * @class Route
 * @package \Core\Routing
 */
final class Route
{
    /**
     * Simpan url route get.
     *
     * @param string $path
     * @param array|string|null $action
     * @param array|string|null $middleware
     * @return Router
     */
    public static function get(string $path, array|string|null $action = null, array|string|null $middleware = null): Router
    {
        return static::router()->get($path, $action, $middleware);
    }

    /**
     * Simpan url route post.
     *
     * @param string $path
     * @param array|string|null $action
     * @param array|string|null $middleware
     * @return Router
     */
    public static function post(string $path, array|string|null $action = null, array|string|null $middleware = null): Router
    {
        return static::router()->post($path, $action, $middleware);
    }

    /**
     * Simpan url route put.
     *
     * @param string $path
     * @param array|string|null $action
     * @param array|string|null $middleware
     * @return Router
     */
    public static function put(string $path, array|string|null $action = null, array|string|null $middleware = null): Router
    {
        return static::router()->put($path, $action, $middleware);
    }

    /**
     * Simpan url route patch.
     *
     * @param string $path
     * @param array|string|null $action
     * @param array|string|null $middleware
     * @return Router
     */
    public static function patch(string $path, array|string|null $action = null, array|string|null $middleware = null): Router
    {
        return static::router()->patch($path, $action, $middleware);
    }

    /**
     * Simpan url route delete.
     *
     * @param string $path
     * @param array|string|null $action
     * @param array|string|null $middleware
     * @return Router
     */
    public static function delete(string $path, array|string|null $action = null, array|string|null $middleware = null): Router
    {
        return static::router()->delete($path, $action, $middleware);
    }

    /**
     * Simpan url route options.
     *
     * @param string $path
     * @param array|string|null $action
     * @param array|string|null $middleware
     * @return Router
     */
    public static function options(string $path, array|string|null $action = null, array|string|null $middleware = null): Router
    {
        return static::router()->options($path, $action, $middleware);
    }

    /**
     * Tambahkan middleware dalam url route.
     *
     * @param array|string $middlewares
     * @return Router
     */
    public static function middleware(array|string $middlewares): Router
    {
        return static::router()->middleware($middlewares);
    }

    /**
     * Tambahkan url lagi dalam route.
     *
     * @param string $prefix
     * @return Router
     */
    public static function prefix(string $prefix): Router
    {
        return static::router()->prefix($prefix);
    }

    /**
     * Tambahkan controller dalam route.
     *
     * @param string $name
     * @return Router
     */
    public static function controller(string $name): Router
    {
        return static::router()->controller($name);
    }

    /**
     * Isi url file route.
     *
     * @return void
     */
    public static function setRouteFromFile(): void
    {
        require_once basepath() . '/routes/routes.php';
    }

    /**
     * Isi url dari cache atau route.
     *
     * @return void
     */
    public static function setRouteFromCacheIfExist(): void
    {
        $cache = basepath() . '/cache/routes.php';
        if (!is_file($cache)) {
            static::setRouteFromFile();
        } else {
            $cache = (array) require_once $cache;
            static::router()->setRoutes($cache);
        }
    }

    /**
     * Ambil objek router.
     *
     * @return Router
     */
    public static function router(): Router
    {
        return App::get()->singleton(Router::class);
    }
}

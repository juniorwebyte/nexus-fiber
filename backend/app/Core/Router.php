<?php
namespace App\Core;

class Router
{
    private $routes = [];

    public function add($method, $path, $callback)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'callback' => $callback
        ];
    }

    public function get($path, $callback) { $this->add('GET', $path, $callback); }
    public function post($path, $callback) { $this->add('POST', $path, $callback); }

    public function dispatch($requestUri, $requestMethod)
    {
        $baseUri = str_replace('/backend/public/index.php', '', $_SERVER['SCRIPT_NAME']);
        $path = str_replace(BASE_URL, '', $requestUri);
        $path = parse_url($path, PHP_URL_PATH);

        // Remover trailing slash
        $path = rtrim($path, '/');
        if (empty($path)) {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === strtoupper($requestMethod)) {
                // Conversão de parâmetros tipo :id
                $pattern = preg_replace('/:[a-zA-Z0-9_]+/', '([a-zA-Z0-9_]+)', $route['path']);
                $pattern = "@^" . $pattern . "$@D";

                if (preg_match($pattern, $path, $matches)) {
                    array_shift($matches); // Remove whole string match
                    
                    if (is_callable($route['callback'])) {
                        return call_user_func_array($route['callback'], $matches);
                    }
                    
                    if (is_array($route['callback'])) {
                        $controllerName = $route['callback'][0];
                        $methodName = $route['callback'][1];
                        $controller = new $controllerName();
                        return call_user_func_array([$controller, $methodName], $matches);
                    }
                }
            }
        }

        http_response_code(404);
        echo "404 - Not Found";
    }
}


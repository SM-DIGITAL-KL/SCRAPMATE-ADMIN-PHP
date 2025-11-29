<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AuthenticateUser;
use App\Http\Middleware\ApiTokenIsValid;

// Configure the application
$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'authusers' => AuthenticateUser::class,
            'apicheck' => ApiTokenIsValid::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    });

// Create the application instance
$application = $app->create();

// Load environment variables from env.txt instead of .env
// Note: Environment is loaded during create(), so we reload from env.txt
$application->loadEnvironmentFrom('env.txt');

// Reload environment variables from env.txt (overwrite any existing values)
$envPath = $application->environmentPath();
if (file_exists($envPath.'/env.txt')) {
    try {
        $dotenv = \Dotenv\Dotenv::create(
            \Illuminate\Support\Env::getRepository(),
            $envPath,
            'env.txt'
        );
        // Use load() to overwrite existing environment variables
        $dotenv->load();
    } catch (\Dotenv\Exception\InvalidFileException|\Dotenv\Exception\InvalidPathException|\Dotenv\Exception\InvalidEncodingException $e) {
        // If env.txt is invalid, fall back to .env (already loaded)
        // This ensures the application still works even if env.txt has issues
    }
}

return $application;

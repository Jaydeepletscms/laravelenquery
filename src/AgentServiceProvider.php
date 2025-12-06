<?php
namespace System\Agent;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

class AgentServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/agent.php', 'agent');
        // bind services here if any
    }

    public function boot(Router $router)
    {
        // publish config, views, migrations if desired
        $this->publishes([
            __DIR__.'/../config/agent.php' => config_path('agent.php'),
        ], 'agent-config');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'agent');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // register middleware alias
        $router->aliasMiddleware('agent.capture', \System\Agent\Http\Middleware\CaptureActivity::class);

        // schedule heartbeat if needed (alternative: provide artisan command)
    }
}

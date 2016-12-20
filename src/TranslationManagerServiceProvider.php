<?php

namespace DragonFly\TranslationManager;

use DragonFly\TranslationManager\Managers\Laravel\Manager;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class TranslationManagerServiceProvider extends ServiceProvider
{
    protected $defer = false;
    
    protected $configPath = '/../config/translations.php';
    
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $this->bootRoutes($router);
        $this->bootViews();
        $this->bootAssets();
        
        // Publish the config file
        $this->publishes([__DIR__ . $this->configPath => config_path('translations.php')], 'config');
        
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../migrations');
    }
    
    /**
     * Register the package's routes.
     *
     * @param \Illuminate\Routing\Router $router
     */
    protected function bootRoutes(Router $router)
    {
        if (!$this->app->routesAreCached())
        {
            $config = $this->app['config']->get('translations.routes', []);
            $config['namespace'] = 'DragonFly\TranslationManager\Http\Controllers';
            $config['laroute'] = true;
            
            $router->group($config, function ()
            {
                require __DIR__ . '/../routes/routes.php';
            });
        }
    }
    
    /**
     * Register the package's views
     */
    protected function bootViews()
    {
        $viewPath = __DIR__ . '/../resources/views';
        
        $this->loadViewsFrom($viewPath, 'translations-manager');
        
        $this->publishes([
            $viewPath => resource_path('views/vendor/translations-manager'),
        ], 'views');
    }
    
    /**
     * Make sure the package's assets are exportable.
     */
    protected function bootAssets()
    {
        $this->publishes([
            __DIR__ . '/../resources/assets' => resource_path('assets/js/dragonfly/translations'),
        ], 'assets');
    }
    
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        // Merge the config
        $this->mergeConfigFrom(__DIR__ . $this->configPath, 'translations');
    
        $this->app->singleton(
            'DragonFly\TranslationManager\Manager',
            function ($app)
            {
                return $app->make('DragonFly\TranslationManager\Managers\Manager');
            }
        );
        
        //Register the laravel translation repository
        $this->app->singleton(
            'DragonFly\TranslationManager\LaravelRepository',
            function ($app, $params)
            {
                return $app->make('DragonFly\TranslationManager\Managers\Drivers\Laravel', $params);
            }
        );
        
        //Register the dimsav translation repository
        $this->app->singleton(
            'DragonFly\TranslationManager\DimsavRepository',
            function ($app, $params)
            {
                return $app->make('DragonFly\TranslationManager\Managers\Drivers\Dimsav', $params);
            }
        );
    }
}
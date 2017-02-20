<?php

namespace Eliurkis\Crud;

use Route;
use Illuminate\Support\ServiceProvider;

class CrudServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Views Files
        $this->loadViewsFrom(resource_path('views/vendor/eliurkis/crud'), 'crud');
        $this->loadViewsFrom(realpath(__DIR__.'/resources/views'), 'crud');

        // Translation Files
        $this->loadTranslationsFrom(resource_path('lang/vendor'), 'eliurkis');
        $this->loadTranslationsFrom(realpath(__DIR__.'/resources/lang'), 'eliurkis');

        // Publish Files
        $this->publishes([__DIR__.'/resources/lang' => resource_path('lang/vendor/eliurkis')], 'lang');
        $this->publishes([__DIR__.'/resources/views' => resource_path('views/vendor/eliurkis/crud')], 'views');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        include __DIR__.'/routes.php';

        // Register provider dependencies
        $this->app->register(\Collective\Html\HtmlServiceProvider::class);

        // Register aliases dependencies
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Form', \Collective\Html\FormFacade::class);
        $loader->alias('Html', \Collective\Html\HtmlFacade::class);
        $loader->alias('Carbon', \Carbon\Carbon::class);
    }

    public static function resource($name, $controller)
    {
        Route::resource($name, $controller, ['parameters' => [
            $name => 'id',
        ]]);
        Route::get($name.'/{id}/delete', $controller.'@destroy')->name($name.'.destroy');
    }
}

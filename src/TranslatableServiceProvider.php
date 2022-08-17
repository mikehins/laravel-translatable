<?php
/**
 * Created by PhpStorm.
 * User: mikeh
 * Date: 2018-05-28
 * Time: 8:27 AM.
 */

namespace Mikehins\Translatable;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class TranslatableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations/');
        
        $this->publishes([
            __DIR__ . '/database/migrations/' => database_path('migrations')
        ], 'migrations');
        
        $this->publishes([
            __DIR__ . '/config/languages.php' => config_path('languages.php'),
        ], 'config');
        
        Collection::macro('for', function ($field, $code) {
            return $this->where('key', $field)->where('locale', $code)->pluck('value')->first() ?? null;
        });
    }
    
    public function register()
    {
    }
}

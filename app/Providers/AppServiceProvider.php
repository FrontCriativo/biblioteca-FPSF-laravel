<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // O front-end espera listas e objetos "crus", sem o envelope { "data": ... }.
        JsonResource::withoutWrapping();

        // Em desenvolvimento, acusa consultas N+1 esquecidas.
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}

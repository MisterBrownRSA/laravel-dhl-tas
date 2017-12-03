<?php

    namespace MisterBrownRSA\DHL\TAS;

    use Illuminate\Support\ServiceProvider;

    class TASProvider extends ServiceProvider
    {
        protected $defer = TRUE;

        /**
         * Bootstrap the application services.
         *
         * @return void
         */
        public function boot()
        {
            $this->publishes([
                __DIR__ . "/config/dhl.php" => config_path('dhl.php'),
            ]);
        }

        /**
         * Register the application services.
         *
         * @return void
         */
        public function register()
        {
            $this->app->singleton(DHLTAS::class, function ($app) {
                return new DHLTAS();
            });
        }

        public function provides()
        {
            return [DHLTAS::class];
        }
    }

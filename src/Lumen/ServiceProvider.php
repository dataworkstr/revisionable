<?php namespace Sofa\Revisionable\Lumen;

use League\Flysystem\Config;
use Illuminate\Support\ServiceProvider as BaseProvider;
use ReflectionClass;

/**
 * @method void publishes(array $paths, $group = null)
 */
class ServiceProvider extends BaseProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            $this->guessPackagePath() . '/config/config.php' => config('sofa_revisionable'),
            $this->guessPackagePath() . '/migrations/' => base_path('/database/migrations'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->bindLogger();

        $this->bindUserProvider();

        $this->bindListener();

        $this->bootModel();
    }

    /**
     * Bind Revisionable logger implementation to the IoC.
     *
     * @return void
     */
    protected function bindLogger()
    {
        $table = $this->app['config']->get('sofa_revisionable.table', 'revisions');

        $options = $this->app['config']->get('sofa_revisionable.options',['max_revision'=>10]);

        $this->app->singleton('revisionable.logger', function ($app) use ($table,$options) {
            return new \Sofa\Revisionable\Lumen\DbLogger($app['db']->connection(), $table,$options);
        });
    }

    /**
     * Bind user provider implementation to the IoC.
     *
     * @return void
     */
    protected function bindUserProvider()
    {
        $userProvider = $this->app['config']->get('sofa_revisionable.userprovider');

        switch ($userProvider) {
            case 'sentry':
                $this->bindSentryProvider();
                break;

            case 'sentinel':
                $this->bindSentinelProvider();
                break;

            case 'jwt-auth':
                $this->bindJwtAuthProvider();
                break;

            default:
                $this->bindGuardProvider();
        }
    }

    /**
     * Bind adapter for Sentry to the IoC.
     *
     * @return void
     */
    protected function bindSentryProvider()
    {
        $this->app->singleton('revisionable.userprovider', function ($app) {
            $field = $app['config']->get('sofa_revisionable.userfield');

            return new \Sofa\Revisionable\Adapters\Sentry($app['sentry'], $field);
        });
    }

    /**
     * Bind adapter for Sentinel to the IoC.
     *
     * @return void
     */
    protected function bindSentinelProvider()
    {
        $this->app->singleton('revisionable.userprovider', function ($app) {
            $field = $app['config']->get('sofa_revisionable.userfield');

            return new \Sofa\Revisionable\Adapters\Sentinel($app['sentinel'], $field);
        });
    }

    /**
     * Bind adapter for JWT Auth to the IoC.
     *
     * @return void
     */
    private function bindJwtAuthProvider()
    {
        $this->app->singleton('revisionable.userprovider', function ($app) {
            $field = $app['config']->get('sofa_revisionable.userfield');

            return new \Sofa\Revisionable\Adapters\JwtAuth($app['tymon.jwt.auth'], $field);
        });
    }

    /**
     * Bind adapter for Illuminate Guard to the IoC.
     *
     * @return void
     */
    protected function bindGuardProvider()
    {
        $this->app->singleton('revisionable.userprovider', function ($app) {
            $field = $app['config']->get('sofa_revisionable.userfield');

            return new \Sofa\Revisionable\Adapters\Guard($app['auth.driver'], $field);
        });
    }

    /**
     * Bind listener implementation to the Ioc.
     *
     * @return void
     */
    protected function bindListener()
    {
        $this->app->bind('Sofa\Revisionable\Listener', function ($app) {
            return new \Sofa\Revisionable\Lumen\Listener($app['revisionable.userprovider']);
        });
    }

    /**
     * Boot the Revision model.
     *
     * @return void
     */
    protected function bootModel()
    {
        $table = $this->app['config']->get('sofa_revisionable.table', 'revisions');

        forward_static_call_array(['\Sofa\Revisionable\Lumen\Revision', 'setCustomTable'], [$table]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return [
            'revisionable.logger',
            'revisionable.userprovider',
        ];
    }

    /**
     * Guess real path of the package.
     *
     * @return string
     */
    public function guessPackagePath()
    {
        $path = (new ReflectionClass($this))->getFileName();

        return realpath(dirname($path).'/../');
    }
}

<?php namespace Yousemble\LaravelOpensrs;

use Illuminate\Support\ServiceProvider;

class LaravelOpenSRSServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('yousemble/laravel-opensrs');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{

    $this->app->bindShared('ys-opensrs', function($app) {
      $cache_provider = $app['cache'];
      $config = $app['config']->get('services.opensrs', null);

      if($config === null){
        $config = $app['config']->get('yousemble/laravel-opensrs::reseller', []);
      }
      return new OpenSRS($config, $cache_provider);
    });
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('ys-opensrs');
	}

}

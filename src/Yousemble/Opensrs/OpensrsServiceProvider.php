<?php namespace Yousemble\Opensrs;

use Illuminate\Support\ServiceProvider;

class OpensrsServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		//$this->package('yousemble/opensrs');
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
        $config = $app['config']->get('yousemble/opensrs::reseller', []);
      }
      return new OpensrsService($config, $cache_provider);
    });
	}


}

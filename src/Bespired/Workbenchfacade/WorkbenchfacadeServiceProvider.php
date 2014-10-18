<?php namespace Bespired\Workbenchfacade;

use Illuminate\Support\ServiceProvider;

class WorkbenchfacadeServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;


	public function boot()
	{
		$this->package('bespired/workbenchfacade');

	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		// add the install command to the application
		$this->app['bespired:workbenchfacade'] = $this->app->share(function($app) {
			return new Commands\WorkbenchFacade($app);
		});
		
		$this->commands('bespired:workbenchfacade');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}

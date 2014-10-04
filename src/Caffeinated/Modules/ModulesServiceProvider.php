<?php

namespace Caffeinated\Modules;

use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('caffeinated/modules');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerServices();

		$this->registerConsoleCommands();
	}

	protected function registerServices()
	{
		$this->app->bindShared('modules.finder', function ($app) {
			return new Finder($app['files'], $app['config']);
		});

		$this->app->bindShared('modules', function ($app) {
			return new Modules(
				$app['modules.finder'],
				$app['config'],
				$app['view'],
				$app['translator'],
				$app['files'],
				$app['url']
			);
		});

		$this->app->booting(function ($app) {
			$app['modules']->register();
		});
	}

	protected function registerConsoleCommands()
	{
		$this->registerMakeCommand();
		$this->registerEnableCommand();
		$this->registerDisableCommand();
		$this->registerMakeMigrationCommand();
		$this->registerMigrateCommand();
		$this->registerSeedCommand();

		$this->commands([
			'modules.make',
			'modules.enable',
			'modules.disable',
			'modules.makeMigration',
			'modules.migrate',
			'modules.seed'
		]);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['modules.finder', 'modules'];
	}

	/**
	 * Register the "module:enable" console command.
	 *
	 * @return Console\ModuleEnableCommand
	 */
	protected function registerEnableCommand()
	{
		$this->app->bindShared('modules.enable', function($app) {
			return new Console\ModuleEnableCommand;
		});
	}

	/**
	 * Register the "module:disable" console command.
	 *
	 * @return Console\ModuleDisableCommand
	 */
	protected function registerDisableCommand()
	{
		$this->app->bindShared('modules.disable', function($app) {
			return new Console\ModuleDisableCommand;
		});
	}

	/**
	 * Register the "module:make" console command.
	 *
	 * @return Console\ModuleMakeCommand
	 */
	protected function registerMakeCommand()
	{
		$this->app->bindShared('modules.make', function($app) {
			$handler = new Handlers\ModuleMakeHandler($app['modules'], $app['files']);

			return new Console\ModuleMakeCommand($handler);
		});
	}

	/**
	 * Register the "module:make-migration" console command.
	 *
	 * @return Console\ModuleMakeMigrationCommand
	 */
	protected function registerMakeMigrationCommand()
	{
		$this->app->bindShared('modules.makeMigration', function($app) {
			$handler = new Handlers\ModuleMakeMigrationHandler($app['modules'], $app['files']);

			return new Console\ModuleMakeMigrationCommand($handler);
		});
	}

	/**
	 * Register the "module:migrate" console command.
	 *
	 * @return Console\ModuleMigrateCommand
	 */
	protected function registerMigrateCommand()
	{
		$this->app->bindShared('modules.migrate', function($app) {
			return new Console\ModuleMigrateCommand($app['modules']);
		});
	}

	/**
	 * Register the "module:seed" console command.
	 *
	 * @return Console\ModuleSeedCommand
	 */
	protected function registerSeedCommand()
	{
		$this->app->bindShared('modules.seed', function($app) {
			return new Console\ModuleSeedCommand($app['modules']);
		});
	}
}

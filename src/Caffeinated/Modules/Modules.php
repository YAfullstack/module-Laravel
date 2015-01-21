<?php
namespace Caffeinated\Modules;

use App;
use Countable;
use Caffeinated\Modules\Handlers\ModulesHandler;
use Caffeinated\Modules\Exceptions\FileMissingException;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class Modules implements Countable
{
	/**
	 * @var ModulesHandler
	 */
	protected $handler;

	/**
	 * @var Repository
	 */
	protected $config;

	/**
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * Constructor method.
	 *
	 * @param ModulesHandler $handler
	 * @param Repository $config
	 * @param Filesystem $files
	 */
	public function __construct(ModulesHandler $handler, Repository $config, Filesystem $files)
	{
		$this->handler = $handler;
		$this->config = $config;
		$this->files  = $files;
	}

	/**
	 * Register the module service provider file from all modules.
	 *
	 * @return mixed
	 */
	public function register()
	{
		foreach ($this->enabled() as $module) {
			$this->registerServiceProvider($module);
		}
	}

	/**
	 * Register the module service provider.
	 *
	 * @param array $module
	 * @return string
	 * @throws \Caffeinated\Modules\Exception\FileMissingException
	 */
	protected function registerServiceProvider($module)
	{
		$module = Str::studly($module['slug']);

		$file = $this->getPath()."/{$module}/Providers/{$module}ServiceProvider.php";

		$namespace = $this->getNamespace().$module."\\Providers\\{$module}ServiceProvider";

		if ( ! $this->files->exists($file)) {
			$message = "Module [{$module}] must have a \"{$module}/Providers/{$module}ServiceProvider.php\" file for bootstrapping purposes.";

			throw new FileMissingException($message);
		}

		App::register($namespace);
	}

	/**
	 * Get all modules.
	 *
	 * @return Collection
	 */
	public function all()
	{
		$modules = array();
		$folders = $this->handler->all();

		if (isset($folders)) {
			foreach ($folders as $module) {
				$modules[] = $this->handler->getJsonContents($module);
			}
		}

		return new Collection($modules);
	}

	/**
	 * Check if given module exists.
	 *
	 * @param string $slug
	 * @return bool
	 */
	public function exists($slug)
	{
		return $this->handler->exists($slug);
	}

	/**
	 * Returns count of all modules.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->all());
	}

	/**
	 * Get modules path.
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->config->get('caffeinated::modules.path');
	}

	/**
	 * Set modules path in "RunTime" mode.
	 *
	 * @param string $path
	 * @return $this
	 */
	public function setPath($path)
	{
		$this->handler->setPath($path);

		return $this;
	}

	/**
	 * Get modules namespace.
	 *
	 * @return string
	 */
	public function getNamespace()
	{
		return $this->config->get('caffeinated::modules.namespace');
	}

	/**
	 * Get path for the specified module.
	 *
	 * @param string $slug
	 * @return string
	 */
	public function getModulePath($slug)
	{
		return $this->handler->getModulePath($slug, true);
	}

	/**
	 * Get a module's properties.
	 *
	 * @param string $slug
	 * @return mixed
	 */
	public function getProperties($slug)
	{
		return $this->handler->getJsonContents($slug);
	}

	/**
	 * Get a module property value.
	 *
	 * @param string $property
	 * @param null $default
	 * @return mixed
	 */
	public function getProperty($property, $default = null)
	{
		return $this->handler->getProperty($property, $default);
	}

	/**
	 * Set a module property value.
	 *
	 * @param  string $property
	 * @param  mixed $value
	 * @return boolean
	 */
	public function setProperty($property, $value)
	{
		return $this->handler->setProperty($property, $value);
	}

	/**
	 * Get all modules by enabled status.
	 *
	 * @param  boolean $enabled
	 * @return array
	 */
	public function getByEnabled($enabled = true)
	{
		$data    = [];
		$modules = $this->all();

		if (count($modules)) {
			foreach ($modules as $module) {
				if ($enabled === true) {
					if ($this->isEnabled($module['slug']))
						$data[] = $module;
				} else {
					if ($this->isDisabled($module['slug']))
						$data[] = $module;
				}
			}
		}

		return $data;
	}

	/**
	 * Simple alias for getByEnabled(true).
	 *
	 * @return array
	 */
	public function enabled()
	{
		return $this->getByEnabled(true);
	}

	/**
	 * Simple alias for getByEnabled(false).
	 *
	 * @return array
	 */
	public function disabled()
	{
		return $this->getByEnabled(false);
	}

	/**
	 * Check if specified module is enabled.
	 *
	 * @param string $slug
	 * @return bool
	 */
	public function isEnabled($slug)
	{
		return $this->getProperty("{$slug}::enabled") === true;
	}

	/**
	 * Check if specified module is disabled.
	 *
	 * @param string $slug
	 * @return bool
	 */
	public function isDisabled($slug)
	{
		return $this->getProperty("{$slug}::enabled") === false;
	}

	/**
	 * Enables the specified module.
	 *
	 * @param string $slug
	 * @return bool
	 */
	public function enable($slug)
	{
		return $this->handler->enable($slug);
	}

	/**
	 * Disables the specified module.
	 *
	 * @param string $slug
	 * @return bool
	 */
	public function disable($slug)
	{
		return $this->handler->disable($slug);
	}
}

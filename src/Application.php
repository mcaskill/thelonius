<?php

/*
 * This file is part of the Thelonius framework.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link      https://github.com/mcaskill/thelonius
 * @copyright Copyright © 2016 Chauncey McAskill
 * @license   https://github.com/mcaskill/thelonius/blob/master/LICENSE (MIT License)
 */

namespace Thelonius;

use InvalidArgumentException;

use Illuminate\Support\Collection;

use Thelonius\Container\Container;
use Thelonius\Provider\DefaultServiceProvider;
use Thelonius\Provider\WordPressServiceProvider;

/**
 * The Thelonius framework class.
 *
 * This is the primary class with which you instantiate,
 * configure, and run a Thelonius Framework application.
 *
 * Based on:
 *
 * - {@link https://github.com/slimphp/Slim/blob/3.x/Slim/App.php Slim}
 * - {@link https://github.com/silexphp/Silex/blob/1.3/src/Silex/Application.php Silex}
 * - {@link https://github.com/laravel/framework/blob/5.2/src/Illuminate/Foundation/Application.php Laravel}
 */
class Application extends Container
{
    /**
     * Current version
     *
     * @var string
     */
    const VERSION = '0.0.0';

    /**
     * Indicates if the framework has "booted".
     *
     * @var boolean
     */
    protected $booted = false;

    /**
     * The base path for the WordPress installation.
     *
     * @var string
     */
    protected $basePath;

    /**
     * to the public / web directory (where WordPress is installed).
     *
     * @var string
     */
    protected $publicPath;

    /**
     * Default settings
     *
     * @var array {
     *     @var string  $base_path      The base path for the Thelonius installation.
     *     @var string  $config_dir     The directory for the Thelonius configuration files, relative to the base path.
     *     @var string  $public_dir     The public / web directory, relative to the base path.
     *     @var string  $wordpress_dir  The directory of the WordPress installation, relative to the public path.
     * }
     */
    private $defaultSettings = [
        'base_path'  => null,
        'config_dir' => 'config',
        'public_dir' => 'www',
        'wp_dir'     => 'wordpress'
    ];

    /**
     * Create a new Thelonius application instance.
     *
     * @param array $values The parameters or objects for the application.
     */
    public function __construct(array $values = [])
    {
        parent::__construct($values);

        $userSettings = isset($values['settings']) ? $values['settings'] : [];
        $this->registerDefaultServices($userSettings);
    }

    /**
     * This function registers the default services that Thelonius needs to work.
     *
     * All services are shared - that is, they are registered such that the
     * same instance is returned on subsequent calls.
     *
     * @param array $userSettings Associative array of application settings
     *
     * @return void
     */
    private function registerDefaultServices($userSettings)
    {
        static::setInstance($this);

        $defaultSettings = $this->defaultSettings;

        /**
         * This service MUST return an array or an instance of \ArrayAccess.
         *
         * @return array|\ArrayAccess
         */
        $this['settings'] = function () use ($userSettings, $defaultSettings) {
            return new Collection(array_merge($defaultSettings, $userSettings));
        };

        $this->register(new DefaultServiceProvider);

        $this->register(new WordPressServiceProvider);
    }

    /**
     * Get the base path of the Thelonius installation.
     *
     * @return string
     */
    public function basePath()
    {
        return realpath($this['settings']['base_path']);
    }

    /**
     * Get the path to the application configuration files.
     *
     * @return string
     */
    public function configPath()
    {
        return realpath($this->basePath().DIRECTORY_SEPARATOR.$this['settings']['config_dir']);
    }

    /**
     * Get the path to the public / web directory.
     *
     * @return string
     */
    public function publicPath()
    {
        return realpath($this->basePath().DIRECTORY_SEPARATOR.$this['settings']['public_dir']);
    }

    /**
     * Get the path to the WordPress installation.
     *
     * @return string
     */
    public function wpPath()
    {
        return realpath($this->publicPath().DIRECTORY_SEPARATOR.$this['settings']['wp_dir']);
    }

    /**
     * Determine if the application has booted.
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }
    }
}

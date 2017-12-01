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

namespace Thelonius\Container;

use InvalidArgumentException;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Interop\Container\Exception\NotFoundException;

use Pimple\Container as PimpleContainer;

/**
 * Thelonius' default DI container is Pimple.
 *
 * Thelonius expects a container that implements {@see ContainerInterface}
 * with these service keys configured and ready for use:
 *
 *  - settings: an array or instance of \ArrayAccess
 *  - environment: an instance of \Slim\Interfaces\Http\EnvironmentInterface
 *  - request: an instance of \Psr\Http\Message\ServerRequestInterface
 *  - response: an instance of \Psr\Http\Message\ResponseInterface
 *  - router: an instance of \Slim\Interfaces\RouterInterface
 *  - foundHandler: an instance of \Slim\Interfaces\InvocationStrategyInterface
 *  - errorHandler: a callable with the signature: function($request, $response, $exception)
 *  - notFoundHandler: a callable with the signature: function($request, $response)
 *  - notAllowedHandler: a callable with the signature: function($request, $response, $allowedHttpMethods)
 *  - callableResolver: an instance of callableResolver
 */
class Container extends PimpleContainer implements ContainerInterface
{
    /**
     * The current globally available container (if any).
     *
     * @var static
     */
    protected static $instance;

    /**
     * Set the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance()
    {
        return static::$instance;
    }
    /**
     * Set the shared instance of the container.
     *
     * @param ContainerInterface  $container  A dependencies container instance.
     */
    public static function setInstance(ContainerInterface $container)
    {
        static::$instance = $container;
    }

    /* ======================================================================
       Methods to satisfy Interop\Container\ContainerInterface
       ====================================================================== */

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string  $id  Identifier of the entry to look for.
     *
     * @throws NotFoundException   No entry was found for this identifier.
     * @throws ContainerException  Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        if ( ! $this->offsetExists($id) ) {
            throw new NotFoundException(sprintf('Identifier "%s" is not defined.', $id));
        }

        try {
            return $this->offsetGet($id);
        } catch (InvalidArgumentException $exception) {
            if ( $this->exceptionThrownByContainer($exception) ) {
                throw new ContainerException(
                    sprintf('Container error while retrieving "%s"', $id),
                    null,
                    $exception
                );
            } else {
                throw $exception;
            }
        }
    }

    /**
     * Returns TRUE if the container can return an entry for the given identifier.
     * Returns FALSE otherwise.
     *
     * @param string  $id  Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has($id)
    {
        return $this->offsetExists($id);
    }

    /**
     * Tests whether an exception needs to be recast for compliance with Container-Interop.
     * This will be if the exception was thrown by Pimple.
     *
     * @param InvalidArgumentException  $exception
     *
     * @return boolean
     */
    private function exceptionThrownByContainer(InvalidArgumentException $exception)
    {
        return preg_match('/^Identifier ".*" is not defined\.$/', $exception->getMessage());
    }
}

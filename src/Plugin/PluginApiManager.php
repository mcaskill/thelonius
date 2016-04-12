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

namespace Thelonius\Plugin;

use InvalidArgumentException;

use Thelonius\Plugin\PluginApiInterface;

/**
 * Manager that interacts with the WordPress plugin API
 *
 * The manager serves as an intermediate for the "hooks" API, also known as "Filters" and "Actions",
 * that WordPress uses to set your plugin in motion.
 *
 * > [ While "actions" and "filters" do different things ], they’re the same under the hood.
 * > They use the same system and much of the same code.
 *
 * @link https://codex.wordpress.org/Plugin_API
 *       Documentation about "hooks" and the Pluing API.
 * @link https://carlalexander.ca/design-system-wordpress-event-management
 *       Based on Carl Alexander's examples from "Design a system: WordPress event management".
 */
class PluginApiManager
{
    /**
     * Adds a callback to a specific hook of the WordPress plugin API.
     *
     * @uses \WP\add_filter()
     *
     * @param string    $name         The name of the hook to which the $callback is hooked.
     * @param callable  $callback     A callable function to run when the hook is called.
     * @param integer   $priority     Optional. Used to specify the order in which the functions
     *                                 associated with a particular action are executed. Default 10.
     *                                 Lower numbers correspond with earlier execution,
     *                                 and functions with the same priority are executed
     *                                 in the order in which they were added to the action.
     * @param integer  $acceptedArgs  Optional. The number of arguments the function accepts. Default 1.
     *
     * @return self
     */
    public function addCallback(
        $name,
        $callback,
        $priority = PluginApiInterface::DEFAULT_PRIORITY,
        $acceptedArgs = PluginApiInterface::DEFAULT_ACCEPTED_ARGS
    )
    {
        add_filter($name, $callback, $priority, $acceptedArgs);

        return $this;
    }

    /**
     * Removes the given callback from the given hook.
     *
     * The WordPress plugin API only removes the hook if the callback and priority match a registered hook.
     *
     * @uses \WP\remove_filter()
     *
     * @param string    $name      The name of the hook to which the $callback is registered to.
     * @param callable  $callback  The callback which should be removed.
     * @param integer   $priority  Optional. The priority of the $callback. Default 10.
     *
     * @return boolean   Whether the function existed before it was removed.
     */
    public function removeCallback(
        $name,
        $callback,
        $priority = PluginApiInterface::DEFAULT_PRIORITY
    )
    {
        if ( ! remove_filter($name, $callback, $priority) ) {
            if ( ! is_callable($callback, false, $callable) ) {
                if ( is_object($callback) ) {
                    $callable = get_class($callback);
                } elseif ( is_string($callback) ) {
                    $callable = $callback;
                } else {
                    $callable = gettype($callback);
                }
            }

            throw new InvalidArgumentException(
                sprintf(
                    'The "%2$s" callback was not registered to the "%1$s" hook.',
                    $name,
                    $callable
                )
            );
        }

        return $this;
    }

    /**
     * Checks the WordPress plugin API to see if the given hook has the given callback.
     *
     * The priority of the callback will be returned or FALSE. If no callback is given, it will return TRUE or FALSE
     * if there's any callbacks registered to the hook.
     *
     * When using the $callback argument, this function may return a non-boolean value that evaluates to FALSE
     * (e.g. "0"), so use the `===` operator for testing the return value.
     *
     * @uses \WP\has_filter()
     *
     * @param string            $name      The name of the hook.
     * @param callable|boolean  $callback  Optional. The callback to check for. Default FALSE.
     *
     * @return boolean|integer
     */
    public function hasCallback($name, $callback = false)
    {
        return has_filter($name, $callback);
    }

    /**
     * Executes all the callbacks registered with the given hook.
     *
     * @uses \WP\do_action()
     *
     * @param  string  $name      The name of the action to be executed.
     * @param  mixed   $args,...  Optional. Additional arguments which are passed on to the functions hooked to the action.
     *                            Default empty.
     * @return mixed|void
     */
    public function execute( $name )
    {
        # unset($name);

        $args = func_get_args();

        return call_user_func_array('do_action', $args);
    }

    /**
     * Filters the given value by applying all the changes from the callbacks
     * registered with the given hook. Returns the filtered value.
     *
     * @uses \WP\apply_filters()
     *
     * @param string  $name   The name of the filter hook.
     * @param mixed   $value  The value on which the filters hooked to `$tag` are applied on.
     * @param mixed   $var    Additional variables passed to the functions hooked to `$tag`.
     *
     * @return mixed   The filtered value after all hooked functions are applied to it.
     */
    public function filter( $name, $value )
    {
        # unset($name, $value);

        $args = func_get_args();

        return call_user_func_array('apply_filters', $args);
    }

    /**
     * Retrieve the name of the hook that the WordPress plugin API is executing.
     *
     * @uses \WP\current_filter()
     *
     * @return string|boolean  Returns the name of the current filter or action
     *                         or FALSE if it isn't executing a hook.
     */
    public function getCurrentHook()
    {
        return current_filter();
    }
}

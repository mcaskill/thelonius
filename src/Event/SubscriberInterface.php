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

namespace Thelonius\Event;

/**
 * A Subscriber knows what specific WordPress plugin API hooks it wants to register to.
 *
 * When an EventManager adds a Subscriber, it gets all the hooks that it wants to
 * register to. It then registers the subscriber as a callback with the WordPress
 * plugin API for each of them.
 *
 * @author Carl Alexander <contact@carlalexander.ca>
 * @link   https://carlalexander.ca/design-system-wordpress-event-management
 *         Based on Carl Alexander's examples from "Design a system: WordPress event management".
 */
interface SubscriberInterface
{
    /**
     * Returns an array of hooks that this subscriber wants to register with
     * the WordPress plugin API.
     *
     * The array key is the name of the hook. The value can be:
     *
     * - A callback
     *   - A function by its name as a string
     *   - A method by its name as a string (of the subscriber)
     *   - A method as an array (`[ object, 'method_name' ]`)
     *   - A Closure (anonymous function)
     * - An array with the method name and priority
     * - An array with the method name, priority, and number of accepted arguments
     *
     * For instance:
     *
     * - `[ 'hook_name' => 'function_name' ]`
     * - `[ 'hook_name' => 'method_name' ]`
     * - `[ 'hook_name' => [ object, 'method_name' ] ]`
     * - `[ 'hook_name' => Closure ]`
     * - `[ 'hook_name' => [ 'method_name', $priority ] ]`
     * - `[ 'hook_name' => [ 'method_name', $priority, $acceptedArgs ] ]`
     *
     * @return array
     */
    public static function getSubscribedEvents();
}

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

use InvalidArgumentException;

use Thelonius\Container\DependentInterface;
use Thelonius\Event\EventManagerAwareSubscriberInterface;
use Thelonius\Event\SubscriberInterface;
use Thelonius\Plugin\PluginApiInterface;
use Thelonius\Plugin\PluginApiManagerAwareInterface;
use Thelonius\Plugin\PluginApiManagerAwareTrait;

/**
 * Event Manager for the Plugin API
 *
 * The event manager manages events using the WordPress plugin API.
 *
 * @see  \Thelonius\Plugin\PluginApiManager
 * @link https://carlalexander.ca/design-system-wordpress-event-management
 *       Based on Carl Alexander's examples from "Design a system: WordPress event management".
 * @link https://gist.github.com/carlalexander/d439be349fffe01cd381
 *       Carl Alexander's code examples from "Design a system: WordPress event management".
 */
class EventManager implements
    DependentInterface,
    PluginApiManagerAwareInterface
{
    use PluginApiManagerAwareTrait;

    /**
     * Set the event manager's dependencies.
     *
     * @param Container $container The framework's DI container.
     *
     * @return self
     */
    public function setDependencies(Container $container)
    {
        $this->setPluginApiManager($container['pluginApiManager']);

        return $this;
    }

    /**
     * Adds the given event listener to the list of event listeners
     * that listen to the given event.
     *
     * @param string    $name         The name of the event to which the $listener is hooked.
     * @param callable  $listener     A callable function to run when the event is called.
     * @param integer   $priority     Optional. Used to specify the order in which the functions
     *                                associated with a particular action are executed. Default 10.
     *                                Lower numbers correspond with earlier execution,
     *                                and functions with the same priority are executed
     *                                in the order in which they were added to the action.
     * @param integer  $acceptedArgs  Optional. The number of arguments the function accepts. Default 1.
     *
     * @return self
     */
    public function addListener(
        $name,
        $listener,
        $priority = PluginApiInterface::DEFAULT_PRIORITY,
        $acceptedArgs = PluginApiInterface::DEFAULT_ACCEPTED_ARGS
    ) {
        $this->pluginApiManager->addCallback($name, $listener, $priority, $acceptedArgs);

        return $this;
    }

    /**
     * Add an event subscriber.
     *
     * The event manager adds the given subscriber to the list of event listeners
     * for all the events that it wants to listen to.
     *
     * @param SubscriberInterface  $subscriber  The subscriber with events to register.
     *
     * @return self
     */
    public function addSubscriber(SubscriberInterface $subscriber)
    {
        if ($subscriber instanceof EventManagerAwareSubscriberInterface) {
            $subscriber->setEventManager($this);
        }

        if ($subscriber instanceof PluginAPIManagerAwareSubscriberInterface) {
            $subscriber->setPluginApiManager($this->pluginApiManager);
        }

        foreach ($subscriber->getSubscribedEvents() as $name => $parameters) {
            $this->addSubscriberCallback($subscriber, $name, $parameters);
        }

        return $this;
    }

    /**
     * Adds the given subscriber's callback to a specific hook
     * of the WordPress plugin API.
     *
     * @param SubscriberInterface  $subscriber  The subscriber with hooks to register.
     * @param string               $name        The name of the hook.
     * @param mixed                $parameters  Optional. Additional parameters required
     *                                          by \WP\add_filter() and \WP\add_action().
     *
     * @return self
     */
    private function addSubscriberCallback(SubscriberInterface $subscriber, $name, $parameters)
    {
        if (is_callable($parameters)) {
            return $this->addCallback($name, $callback);
        }

        if (is_string($parameters)) {
            $parameters = [ $parameters ];
        }

        if (is_array($parameters) && isset($parameters[0])) {
            if (is_callable($parameters[0])) {
                $callback = $parameters[0];
            } elseif (is_callable([ $subscriber, $parameters[0] ])) {
                $callback = [ $subscriber, $parameters[0] ];
            } else {
                throw new InvalidArgumentException('The event has no callable function.');
            }

            $priority     = ( isset($parameters[1]) ? $parameters[1] : self::DEFAULT_PRIORITY );
            $acceptedArgs = ( isset($parameters[2]) ? $parameters[2] : self::DEFAULT_ACCEPTED_ARGS );

            $this->addCallback($name, $callback, $priority, $acceptedArgs);
        }

        return $this;
    }

    /**
     * Removes the given event listener from the list of event listeners
     * that listen to the given event.
     *
     * @param string    $name      The name of the hook to which the $callback is registered to.
     * @param callable  $callback  The callback which should be removed.
     * @param integer   $priority  Optional. The priority of the $callback. Default 10.
     *
     * @return boolean   Whether the function existed before it was removed.
     */
    public function removeListener(
        $name,
        $listener,
        $priority = PluginApiInterface::DEFAULT_PRIORITY
    ) {
        return $this->pluginApiManager->removeCallback($name, $listener, $priority);
    }

    /**
     * Remove an event subscriber.
     *
     * The event manager removes all the hooks that the given subscriber
     * wants to register with the WordPress Plugin API.
     *
     * @param SubscriberInterface  $subscriber  The subscriber with hooks to remove.
     *
     * @return self
     */
    public function removeSubscriber(SubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscribedEvents() as $name => $parameters) {
            $this->removeSubscriberCallback($subscriber, $name, $parameters);
        }

        return $this;
    }

    /**
     * Removes the given subscriber's callback to a specific hook
     * of the WordPress plugin API.
     *
     * @param SubscriberInterface $subscriber  The subscriber with hooks to remove.
     * @param string              $name        The name of the hook.
     * @param mixed               $parameters  Optional. Additional parameters required
     *                                         by \WP\remove_filter() and \WP\remove_action().
     *
     * @return self
     */
    private function removeSubscriberCallback(SubscriberInterface $subscriber, $name, $parameters)
    {
        if (is_callable($parameters)) {
            return $this->removeCallback($name, $callback);
        }

        if (is_string($parameters)) {
            $parameters = [ $parameters ];
        }

        if (is_array($parameters) && isset($parameters[0])) {
            if (is_callable($parameters[0])) {
                $callback = $parameters[0];
            } elseif (is_callable([ $subscriber, $parameters[0] ])) {
                $callback = [ $subscriber, $parameters[0] ];
            }

            $priority = ( isset($parameters[1]) ? $parameters[1] : self::DEFAULT_PRIORITY );

            $this->removeCallback($name, $callback, $priority);
        }

        return $this;
    }

    /**
     * Retrieve the name of the event that the WordPress plugin API is executing.
     *
     * @return string|boolean  Returns the name of the current filter or action
     *                         or FALSE if it isn't executing a hook.
     */
    public function getCurrentEvent()
    {
        return $this->pluginApiManager->getCurrentHook();
    }
}

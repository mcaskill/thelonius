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

namespace Thelonius\Database;

use InvalidArgumentException;

use wpdb as WP_DB;

/**
 * Database Manager for the Database API
 *
 * The manager wraps around the global {@see WP_DB} instance.
 *
 * @link https://carlalexander.ca/saving-wordpress-custom-post-types-using-interface/
 *       Based on Carl Alexander's examples from "Helping WordPress make friends with the decorator pattern".
 */
class DatabaseManager
{
    /**
     * The WP_DB instance.
     *
     * @var mixed
     */
    private $wpdb;

    /**
     * Returns new DatabaseManager object.
     *
     * @param mixed $wpdb
     */
    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /**
     * Intercepts all calls to the WP_DB object. If the method is tracked, performs analysis on it.
     *
     * @param string  $method     The WP_DB method to call.
     * @param array   $arguments  The parameters to pass on to the $method.
     *
     * @return mixed
     */
    public function __call($method, array $arguments)
    {
        $trackedMethods = [ 'delete', 'get_col', 'get_results', 'get_row', 'get_var', 'query', 'update' ];

        $result = call_user_func_array([ $this->wpdb, $method ], $arguments);

        if ( ! in_array($method, $trackedMethods) ) {
            return $result;
        }

        if ( '' !== $this->wpdb->last_error ) {
            do_action('wpdb_error', $this->wpdb->last_error, $this->wpdb->last_query);
        }

        return $return;
    }

    /**
     * Get the given WP_DB variable.
     *
     * @param string  $variable  The class property to retrieve.
     *
     * @return mixed
     */
    public function __get($variable)
    {
        return $this->wpdb->{$variable};
    }

    /**
     * Sets the value of the given WP_DB variable.
     *
     * @param string  $variable  The class property to set.
     * @param mixed   $value     The value to set on the class property.
     */
    public function __set($variable, $value)
    {
        $this->wpdb->{$variable} = $value;
    }

    /**
     * Checks if the given WP_DB variable is set.
     *
     * @param string  $variable  The class property to test.
     *
     * @return bool
     */
    public function __isset($variable)
    {
        return isset($this->wpdb->{$variable});
    }

    /**
     * Unsets the given WP_DB variable.
     *
     * @param string  $variable  The class property to remove.
     */
    public function __unset($variable)
    {
        unset($this->wpdb->{$variable});
    }
}

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

namespace Thelonius\PostType\Support;

use Thelonius\PostType\Support\AbstractFeature;

/**
 * Single Post Template Files
 *
 * This class provides support for custom post types to use template files much
 * like the `page` post-type. For example, the following path would be used for
 * a `product` post-type:
 *
 * - `product-{slug}.php – If the product slug is `dmc-12`, WordPress will look to use `product-dmc-12.php`.
 * - `product-{id}.php – If the product ID is _6_, WordPress will look to use `product-6.php`.
 * - `product.php
 * - `singular.php
 * - `index.php
 *
 * To enable special single post template files for a custom post type
 *
 * You can enable/disable this feature with:
 *
 * ```
 * add_post_type_support( 'product', 'single-template' );
 * ```
 *
 * @link https://github.com/DennisHalmstad/wp-customPostTypreRedirect.class
 *       Based on Dennis Halmstad's repostory.
 */
class SinglePostTemplate extends AbstractFeature
{
    private static $defOptions = [
        'ignorePostTypes' => [
            'post',
            'page',
            'attatchment',
            'revision',
            'nav_menu_item'
        ],
        'folders' => [
            '{$postType}-templates/',
            'custom-templates/',
            ''
        ],
        'searchParent' => false,
        'depth' => 1,
        'devMode' =>false
    );

    private $options;

    public function __construct($options = [])
    {
        //merge and save options so we atleast have default values
        //unsued options will be ignored anyway
        $this->options = array_merge(self::$defOptions, !is_array($options) ? [] : $options);

        add_action('template_redirect', [ &$this, 'checkForRedirect' ]);
    }

    public function __destruct()
    {
        remove_action('template_redirect', [ &$this, 'checkForRedirect' ]);
    }

    //check for and do redirect
    public function checkForRedirect()
    {
        //retrive this post (if it returns empty flush rewriterule)
        global $post;
        $thisPostType = get_post_type($post);

        //if custom and single
        if ((!in_array($thisPostType, $this->options['ignorePostTypes'])) && is_single()) {

            //get transient
            $transVal = get_transient($thisPostType."-" .$post->ID);
            if ($transVal && !$this->options['devMode']) {

                //if value was retrived use it
                require_once $transVal;
                exit();
            }

            //filenames of files we are looking foor sinze get_files returns a relative path as key
            //build matching record.
            $filesToFind = [];
            foreach ($this->options['folders'] as $folder) {
                $folder = str_replace('{$postType}', $thisPostType, $folder);
                $filesToFind[] = $folder . $thisPostType . '-' . $post->ID . '.php';
                $filesToFind[] = $folder . $thisPostType . '-' . $post->post_name . '.php';
                $filesToFind[] = $folder . $thisPostType . '.php';
            }

            //retrive files
            $files = wp_get_theme()->get_files('php', $this->options['depth'], $this->options['searchParent']);

            //separate loop so we can set desired order of files
            foreach ($filesToFind as $fileToFind) {
                if (array_key_exists($fileToFind, $files)) {
                    //store in transient so we can have it "cached"
                    set_transient($thisPostType."-" .$post->ID, $files[$fileToFind], 1800);
                    require_once $files[$fileToFind];
                    exit();
                }
            }
        }
    }
}

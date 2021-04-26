<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/** add bootstrap to theme **/


/** add css to theme **/
function nhomn_styles(){
    wp_enqueue_style('theme-bootstrap', "https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css", '4.0.0');
    wp_enqueue_style('main-style', get_template_directory_uri() . "/style.css", 'all');
    wp_enqueue_style('main-style');
}
add_action('wp_enqueue_scripts', 'nhomn_styles');

/** theme support **/

if(!function_exists('nhomn_theme_setup')){
    /** setup **/
    function nhomn_theme_setup(){
        add_theme_support('post-formats', 
                array(
                    'video',
                    'image',
                    'audio',
                    'gallery',
                ));
        /** create menu for theme **/
        register_nav_menu('primary-menu', __('Primary Menu', 'nhomn'));
        
        /** create sidebar for theme **/
        $sidebar = array(
            'name' => __('Main Sidebar', 'nhomn'),
            'id' => 'main-sidebar',
            'description' => 'Main sidebar for my theme',
            'class' => 'main-sidebar',
            'before_title' => '<h3 class="widgettitle">',
            'after_sidebar' => '</h3>',
        );
        register_sidebar($sidebar);
    }
    add_action('init', 'nhomn_theme_setup');
}

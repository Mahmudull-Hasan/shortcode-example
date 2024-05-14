<?php

/**
 * Plugin Name: 		Shortcode Plugin
 * Description: 		Shortcode is very usefull for WordPress
 * Version: 			1.0.0
 * Author: 				Hasan Mahmud
 * Author URI: 			http://hasan.me
 * Plugin URI: 			http://google.com
 * text-domain: 		shortcode
 * Domain Path:       	/languages
 */


 Class Shortcode {
    public function __construct() {
        add_action('init', [$this, 'init']);
    }

    public function init() {
        //creating a shortcode
        add_shortcode('greet', [$this, 'greet']);

        //create a shortcode with name attribute
        add_shortcode('greetings', [$this, 'greetings']);

        //hello shortcode
        add_shortcode( 'hello', [$this, 'hello'] );

        // parent child shortcode [parent][child][/child][/parent]
        add_shortcode('parent', [$this, 'parent']);
        add_shortcode('child', [$this, 'child']);

        //creaet a video shortcode to display youtube and vimeo video
        add_shortcode('video', [$this, 'video']);

        //create xkcd comic shortcode like [xkcd comic='936']
        add_shortcode('xkcd', [$this, 'xkcd_comic']);

        //register custom post type 'time' with only title
        register_post_type('time', [
            'public' => false,
            'show_ui' => true,
            'label'  => 'Time',
            'supports' => ['title']
        ]);

        //shortcode column add
        add_filter('manage_time_posts_columns', [$this, 'time_column']);
        add_action('manage_time_posts_custom_column', [$this, 'time_column_content'], 10, 2);


        //add metaboxes
        add_action('add_meta_boxes', [$this, 'add_time_meta_boxes']);
        add_action('save_post', [$this, 'save_time_meta_box_date']);

        //Time shortcode
        add_shortcode( 'time', [$this, 'time_shortcode'] );
    }

    //Time shortcode
    public function time_shortcode($atts) {
        $default_value = [
            'id' => '',
        ];

        $attributes = shortcode_atts( $default_value, $atts ) ;

        if ( empty($attributes['id'])) {
            return "<p>Please provide a valid post id</p>";
        }

        $timezone   = get_post_meta($attributes['id'], 'timezone', true);
        $country    = get_post_meta($attributes['id'], 'country', true);
        $city       = get_post_meta($attributes['id'], 'city', true);

        $time       = new DateTime('now', new DateTimeZone($timezone));
        //$time->setTimezone(new DateTimeZone('UTC'));

        return "<p>Time in {$city}, {$country} is {$time->format('Y-m-d H:i:s')}</p>";

    }

    //save_time_meta_box_date
    public function save_time_meta_box_date($post_id) {
        //Check if nonce is set
        if ( !isset ($_POST['time_meta_box_nonce'])) {
            return;
        }

        //Verify Nonce
        if ( !wp_verify_nonce($_POST['time_meta_box_nonce'], 'time_meta_box')) {
            return;
        }

        // Save timezone, country, city meta fields
        if (isset ($_POST['timezone'])) {
            update_post_meta($post_id, 'timezone', sanitize_text_field($_POST['timezone']));
        }
        if (isset ($_POST['country'])) {
            update_post_meta($post_id, 'country', sanitize_text_field($_POST['country']));
        }
        if (isset ($_POST['city'])) {
            update_post_meta($post_id, 'city', sanitize_text_field($_POST['city']));
        }
     }

    //add metaboxes
    public function add_time_meta_boxes() {
        add_meta_box('time_meta_box', 'Time', [$this, 'time_meta_box_content'], 'time', 'side', 'default');
    }

    public function time_meta_box_content($post) {
        wp_nonce_field( 'time_meta_box', 'time_meta_box_nonce' ) ;

        // Retrieve existing values for fields
        $utimezone  = get_post_meta($post->ID, 'timezone', true);
        $country    = get_post_meta($post->ID, 'country', true);
        $city       = get_post_meta($post->ID, 'city', true);

        ?>
            <p>
                <label for="timezone">Timezone: </label>
                <!-- select Dropdown -->
                <select name="timezone" id="timezone">
                    <option value="GMT">GMT</option>
                    <option value="CET">CET</option>
                    <option value="CEST">CEST</option>
                    <option value="EST">EST</option>
                    <option value="PST">PST</option>
                    <option value="GMT+1">GMT+1</option>
                    <option value="GMT+2">GMT+2</option>
                    <option value="GMT+3">GMT+3</option>
                    <?php
                        $timezones = timezone_identifiers_list();
                        foreach ($timezones as $timezone) {
                            echo "<option value='{$timezone}' ". selected( $timezone, $utimezone, false ). "> {$timezone} </option>";
                        }
                        
                    ?>
                    
                </select>
            </p>

            <p>
                <label for="country">Country: </label>
                <input type="text" id="country" name="country" value="<?php echo esc_attr($country); ?>" />
            </p>

            <p>
                <label for="city">City: </label>
                <input type="text" id="city" name="city" value="<?php echo esc_attr($city); ?>" >
            </p>
        <?php
    }


    //Manage Shortcode Columns
    public function time_column($columns) {
        $columns['shortcode'] = 'Shortcode';
        return $columns;
    }

    //Manage Shortcode custom column
    public function time_column_content($column, $post_id) {
        if ($column=='shortcode') {
            echo "[time id='$post_id']";
        }

    }

    //xkcd comic shortcode
    public function xkcd_comic($atts) {
        $default_value = [
            'comic' =>936,
        ];

        $attributes = shortcode_atts( $default_value, $atts ) ;

        $response   = wp_remote_get("https://xkcd.com/{$attributes['comic']}/info.0.json");
        $body       = wp_remote_retrieve_body($response);
        $data       = json_decode($body, true);

        $image      = esc_url($data['img']);
        $title      = esc_attr($data['title']);
        $alt        = esc_attr($data['alt']);

        return "<p><img src='{$image}' title='{$title}' alt='{$alt}' /></p> ";
    }

    //video shortcode
    public function video($atts) {
        $default_value= [
            'type'      => 'youtube',
            'id'        => '',
            'width'     => '560',
            'height'    => '315',
        ];

        //$atts['type'] = sanitize_text_field( $atts['type'] );

        $attributes = shortcode_atts($default_value, $atts);

        $attributes['type']     = sanitize_text_field($attributes['type']);
        
        $attributes['id']       = esc_attr($attributes['id']);
        $attributes['width']    = esc_attr($attributes['width']);
        $attributes['height']   = esc_attr($attributes['height']);

        if ($attributes['type'] == 'youtube') {
            return "<p><iframe width='{$attributes['width']}' height='{$attributes['height']}' src='https://www.youtube.com/embed/{$attributes['id']}' frameborder='0' allow='accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture' allowfullscreen></iframe></p>";
        } else if ($attributes['type'] == 'vimeo') {
            return "<p><iframe src='https://player.vimeo.com/video/{$attributes['id']}' width='{$attributes['width']}' height='{$attributes['height']}' frameborder='0' allow='autoplay; fullscreen' allowfullscreen></iframe><p>";
        } else {
            return "<p>Invalid Video Type</p>";
        }
    }

    //parent shortcode
    function parent($atts, $content = null) {
        $content = do_shortcode($content);
        return "<div style='border: 1px solid red; padding: 10px;'>This is Parent - {$content}</div>";
    }

    //child shortcode
    function child($atts, $content = null) {
        return "<div style='border: 1px solid green; padding: 10px;'>{$content}</div>";
    }

    // hello shortcode
    public function hello($atts, $content= null) {

        $default_value= [
            'name' => 'Guest',
        ];
        $attributes = shortcode_atts($default_value, $atts);

        return "<p>Hello, {$attributes['name']}! {$content}</p>";
    }

    //greetings shortcode
    public function greetings($atts) {
        $default_value= [
            'name' => 'Guest',
        ];
        $attributes = shortcode_atts($default_value, $atts);

        //others rules
        //  $attributes = shortcode_atts( [
        //     'name' => 'Guest',
        //  ], $atts );

        $greetings = "<p>Good Morning, {$attributes['name']}!</p>";

        return $greetings;

    }
    
    public function greet() {
        return "<p>Good Morning</p>";
    }
 }

 new Shortcode();
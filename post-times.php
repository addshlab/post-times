<?php
/**
Plugin Name: Post Times
Plugin URI: 
Description: 
Author: https://add.sh/ 
Version: 0.1
*/

new postTimes();

class postTimes {

    function __construct() {
        add_action( 'rest_api_init', array( $this, 'post_times_endpoint' ) );
        add_shortcode( 'post_times_heatmap', array( $this, 'get_heatmap') );
	add_action( 'wp_enqueue_scripts', array( $this, 'post_times_scripts' ) );
	add_action( 'wp', array( $this, 'load_heatmap_js' ) );
    }

    public function load_heatmap_js() {
        global $post;
        if ( has_shortcode( $post->post_content, 'post_times_heatmap' ) ) {
	    add_action( 'wp_footer', array( $this, 'heatmap_js' ) );
	}
    } 

    public function post_times_scripts() {
        wp_enqueue_style( 'post-times-style', plugins_url() . '/post-times/lib/cal-heatmap.css' );
	wp_register_script( 'post-times-d3', plugins_url() . '/post-times/lib/d3.min.js', array(), '3.5.17', false );
	wp_register_script( 'post-times-heatmap', plugins_url() . '/post-times/lib/cal-heatmap.min.js', array( 'post-times-d3' ), '3.6.1', false );
	wp_enqueue_script( 'post-times-d3', plugins_url() . '/post-times/lib/d3.min.js', array(), '3.5.17', false );
	wp_enqueue_script( 'post-times-heatmap', plugins_url() . '/post-times/lib/cal-heatmap.min.js', array( 'post-times-d3' ), '3.6.1', false );
    }

    public function post_times_endpoint() {
	register_rest_route( 'addsh/v1', '/post_times', array (
            'methods'  => 'GET',
	    'callback' => array( $this, 'get_post_times' ),
        ));
    }

    public function get_post_times() {
        global $wpdb;

	$now = new DateTime( 'now', new DateTimeZone( 'Asia/Tokyo' ) );
	$today    = new DateTimeImmutable( $now->format('Y-m-dT24:00') );
        $past     = new DateTimeImmutable( $today->modify( '-365 days' )->format('Y-m-d') );
        $interval = new DateInterval( 'P1D' );
        $period   = new DatePeriod( $past, $interval, $today );
        $count_array = array();

        foreach ( $period as $day ) {
	    $date = $day->format( 'Y-m-d' );
            $date_u = new DateTime( $date );
            $post_count = $wpdb->get_col( "
SELECT count(ID)
FROM $wpdb->posts
WHERE
    post_date LIKE '$date%'
AND
    post_type='post'
AND
    post_status='publish'
    " );
            $count_array[$date_u->format( 'U' )] = (int) $post_count[0];  
        } 

        return $count_array;
    }

    public function heatmap_js() {
        $js = <<<EOT
<script>
var now = new Date();
var cal = new CalHeatMap();
cal.init({
    data: "/wp-json/addsh/v1/post_times",
    itemSelector: '#post-times-heatmap',
    domain: 'year',
    subDomain: 'day',
    domainLabelFormat: '%Y',
//    domainLabelFormat: '%b %Y',
//    start: new Date(now.getFullYear(), now.getMonth() - 11),
    range: 1,
    legend: [1],
    legendColors: {
        min: "#efefef",
        max: "steelblue",
        empty: "#efefef"
    },
    tooltip: true,
    cellSize: 8,
    highlight: ["now", now]
});
</script> 
EOT;
        echo $js;
    }

    public function get_heatmap() {

        return '<div id="post-times-heatmap"></div>';

    }

} // end class


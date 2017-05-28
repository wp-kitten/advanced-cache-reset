<?php
/*
Plugin Name: Advanced Cache Reset
Plugin URI: https://github.com/wp-kitten/advanced-cache-reset
Description: [wp-kitten] This plugin will reset your WordPress cache. WPMU is supported. Clears the cache of the following plugins: W3 Total Cache, WP Super Cache, WP Fastest Cache, Autoptimize, Cachify, CloudFlare, Comet Cache, Hyper Cache, LiteSpeed Cache, Nginx Helper, SG Optimizer and on the following themes: Kallyas
Version: 10000.1
Author: wp-kitten
Author URI: https://github.com/wp-kitten/
Text Domain: advanced-cache-reset
*/
if( ! defined( 'ABSPATH' ) ){
	exit;
}

/**
 * Class AdvancedCacheReset
 *
 * The plugin's main class. Standard Singleton
 */
class AdvancedCacheReset
{
	private static $_instance = null;

	public static function getInstance()
	{
		if( empty(self::$_instance) || ! (self::$_instance instanceof self)){
			self::$_instance = new self;
		}
		return self::$_instance;
	}
	private function __construct() {
		add_action( 'admin_bar_menu', array($this, 'addTopBarButton'), 51 );
		add_action( 'admin_init', array($this, 'clearCache'), 51 );
		add_action( 'init', array( $this, 'loadTextDomain' ) );
	}

	public function addTopBarButton( $wp_admin_bar )
	{
		$args = array(
			'id'    => 'adv_cache_reset_button',
			'title' => __( 'Adv Cache Reset', 'advanced-cache-reset' ),
			'href'  => add_query_arg( array( 'adv_cache_reset' => '1' ), admin_url() ),
			'meta'  => array( 'class' => 'adv_cache_reset_button' )
		);
		$wp_admin_bar->add_node( $args );
	}

	public function clearCache()
	{
		//#! Refuse these requests
		$doingAjax = ( defined( 'DOING_AJAX' ) && DOING_AJAX );
		$doingCron = ( defined( 'DOING_CRON' ) && DOING_CRON );
		$ajaxRequest = ( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' );
		if ( $doingAjax || $doingCron || $ajaxRequest ) {
			return;
		}
		if( ! isset($_REQUEST['adv_cache_reset']) || (int)$_REQUEST['adv_cache_reset'] != 1 ){
			return;
		}

		//#! W3 Total Cache (https://wordpress.org/plugins/w3-total-cache/)
		if( function_exists( 'w3tc_flush_all' ) ){
			w3tc_flush_all();
		}
		//#! WP Super Cache (https://wordpress.org/plugins/wp-super-cache/)
		if( function_exists('wp_cache_clean_cache'))
		{
			//#! wth?!!
			global $file_prefix;
			if( ! empty($file_prefix) ){
				wp_cache_clean_cache( $file_prefix, true );
			}
		}
		//#! WP Fastest Cache (https://wordpress.org/plugins/wp-fastest-cache/)
		if( class_exists('WpFastestCache') && method_exists( 'WpFastestCache', 'deleteCache' ) && isset($GLOBALS["wp_fastest_cache"])){
			call_user_func( array('WpFastestCache', 'deleteCache'), true );
		}

		//#! Autoptimize (https://wordpress.org/plugins/autoptimize/)
		if( class_exists('autoptimizeCache') && method_exists('autoptimizeCache', 'clearall')){
			autoptimizeCache::clearall();
		}

		//#! Cachify (https://wordpress.org/plugins/cachify/)
		if( class_exists('Cachify') && method_exists('Cachify', 'flush_total_cache')){
			Cachify::flush_total_cache( true );
		}

		//#! CloudFlare (https://wordpress.org/plugins/cloudflare/)
		if( class_exists('\CF\WordPress\Hooks') && method_exists('\CF\WordPress\Hooks', 'purgeCacheEverything')){
			$h = new \CF\WordPress\Hooks();
			$h->purgeCacheEverything();
		}

		//#! Comet Cache (https://wordpress.org/plugins/comet-cache/)
		if( class_exists('WebSharks\CometCache\Classes\ApiBase') && method_exists('WebSharks\CometCache\Classes\ApiBase', 'purge')){
			WebSharks\CometCache\Classes\ApiBase::purge();
		}

		//#! Hyper Cache (https://wordpress.org/plugins/hyper-cache/)
		if( has_action( 'hyper_cache_clean' ) ){
			do_action( 'hyper_cache_clean' );
		}

		//#! LiteSpeed Cache (https://wordpress.org/plugins/litespeed-cache/)
		if( class_exists( 'LiteSpeed_Cache' ) ){
			$instance = LiteSpeed_Cache::plugin();
			$instance->purge_all();
		}

		//#! Nginx Helper (https://wordpress.org/plugins/nginx-helper/)
		global $rt_wp_nginx_purger;
		if( ! empty($rt_wp_nginx_purger) && is_callable( array($rt_wp_nginx_purger, 'true_purge_all'))){
			$rt_wp_nginx_purger->true_purge_all();
		}

		//#! SG Optimizer (https://wordpress.org/plugins/sg-cachepress/)
		if( function_exists('sg_cachepress_purge_cache')){
			sg_cachepress_purge_cache();
		}

		//#! Kallyas Theme v4.13+ (https://themeforest.net/item/kallyas-responsive-multipurpose-wordpress-theme/4091658)
		if( function_exists('znhgkl_theme_cache')){
			znhgkl_theme_cache();
		}
	}

	/**
	 * Enable plugin to be translated
	 */
	public function loadTextDomain()
	{
		load_plugin_textdomain( 'advanced-cache-reset', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

}

AdvancedCacheReset::getInstance();

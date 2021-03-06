<?php
/**
 * Class file for the Object_Sync_Sf_Rest class.
 *
 * @file
 */

if ( ! class_exists( 'Object_Sync_Salesforce' ) ) {
	die();
}

/**
 * Create WordPress REST API functionality
 */
class Object_Sync_Sf_Rest {

	protected $wpdb;
	protected $version;
	protected $slug;
	protected $option_prefix;
	protected $wordpress;
	protected $salesforce;
	protected $mappings;
	protected $push;
	protected $pull;

	/**
	* Constructor which sets up rest methods
	*
	* @param object $wpdb
	* @param string $version
	* @param string $slug
	* @param string $option_prefix
	* @param object $wordpress
	* @param object $salesforce
	* @param object $mappings
	* @param object $push
	* @param object $pull
	* @throws \Exception
	*/
	public function __construct( $wpdb, $version, $slug, $option_prefix, $wordpress, $salesforce, $mappings, $push, $pull ) {
		$this->wpdb          = $wpdb;
		$this->version       = $version;
		$this->slug          = $slug;
		$this->option_prefix = isset( $option_prefix ) ? $option_prefix : 'object_sync_for_salesforce_';
		$this->wordpress     = $wordpress;
		$this->salesforce    = $salesforce;
		$this->mappings      = $mappings;
		$this->push          = $push;
		$this->pull          = $pull;

		$this->sfwp_transients = $this->wordpress->sfwp_transients;

		$this->namespace = $this->slug;

		$this->add_actions();

	}

	/**
	* Create the action hooks to create the reset methods
	*
	*/
	public function add_actions() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	* Register REST API routes
	*
	* @throws \Exception
	*/
	public function register_routes() {
		$namespace   = $this->namespace;
		$method_list = WP_REST_Server::ALLMETHODS;
		register_rest_route( $namespace, '/(?P<class>([\w-])+)/', array(
			array(
				'methods'             => $method_list,
				'args'                => array(
					'class'                  => array(
						'validate_callback' => array( $this, 'check_class' ),
						'required'          => true,
					),
					'salesforce_object_type' => array(
						'type' => 'string',
					),
					'salesforce_id'          => array(
						'type' => 'string',
					),
					'wordpress_object_type'  => array(
						'type' => 'string',
					),
				),
				'permission_callback' => array( $this, 'can_process' ),
				'callback'            => array( $this, 'process' ),
			),
		) );

	}

	/**
	* Check for a valid class from the parameter
	*
	* @param string $class
	* @return bool
	*/
	public function check_class( $class ) {
		if ( is_object( $this->{ $class } ) ) {
			return true;
		}
		return false;
	}

	/**
	* Check for a valid ID from the parameter
	*
	* @param string $id
	* @return bool
	*/
	public function check_id( $id ) {
		if ( is_object( $class ) ) {
			return true;
		}
		return false;
	}

	/**
	* Check to see if the user has permission to do this
	*
	* @param WP_REST_Request $request
	* @throws \Exception
	*/
	public function can_process( WP_REST_Request $request ) {
		// unless we specify it here, the method will not be allowed unless the user has configure_salesforce capability
		$http_method = $request->get_method();
		$class       = $request->get_url_params()['class'];
		switch ( $class ) {
			case 'salesforce':
				if ( ! in_array( $http_method, explode( ',', WP_REST_Server::ALLMETHODS ) ) ) {
					return new WP_Error( 'rest_forbidden', esc_html__( 'This kind of request is not allowed.', 'object-sync-for-salesforce' ), array( 'status' => 401 ) );
				}
				if ( ! current_user_can( 'configure_salesforce' ) ) {
					return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have permissions to view this data.', 'object-sync-for-salesforce' ), array( 'status' => 401 ) );
				}
				break;
			case 'mappings':
				if ( ! in_array( $http_method, explode( ',', WP_REST_Server::ALLMETHODS ) ) ) {
					return new WP_Error( 'rest_forbidden', esc_html__( 'This kind of request is not allowed.', 'object-sync-for-salesforce' ), array( 'status' => 401 ) );
				}
				if ( ! current_user_can( 'configure_salesforce' ) ) {
					return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have permissions to view this data.', 'object-sync-for-salesforce' ), array( 'status' => 401 ) );
				}
				break;
			case 'pull':
				if ( ! in_array( $http_method, array( 'GET', 'POST', 'PUT' ) ) ) {
					return new WP_Error( 'rest_forbidden', esc_html__( 'This kind of request is not allowed.', 'object-sync-for-salesforce' ), array( 'status' => 401 ) );
				}
				break;
			case 'push':
				if ( ! in_array( $http_method, array( 'POST', 'PUT' ) ) ) {
					return new WP_Error( 'rest_forbidden', esc_html__( 'This kind of request is not allowed.', 'object-sync-for-salesforce' ), array( 'status' => 401 ) );
				}
				break;
			default:
				if ( ! current_user_can( 'configure_salesforce' ) ) {
					return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have permissions to view this data.', 'object-sync-for-salesforce' ), array( 'status' => 401 ) );
				}
				break;
		}
		return true;
	}

	/**
	* Process the REST API request
	*
	* @param WP_REST_Request $request
	* @return $result
	*/
	public function process( WP_REST_Request $request ) {
		// see methods: https://developer.wordpress.org/reference/classes/wp_rest_request/
		//error_log( 'request is ' . print_r( $request, true ) );
		$http_method = $request->get_method();
		$route       = $request->get_route();
		$url_params  = $request->get_url_params();
		$body_params = $request->get_body_params();
		$class       = $request->get_url_params()['class'];
		$api_call    = str_replace( '/' . $this->namespace . $this->version . '/', '', $route );
		//error_log( 'api call is ' . $api_call . ' and params are ' . print_r( $params, true ) );
		$result = '';
		switch ( $class ) {
			case 'salesforce':
				break;
			case 'mappings':
				break;
			case 'pull':
				if ( 'GET' === $http_method ) {
					$result = $this->pull->salesforce_pull_webhook( $request );
				}
				if ( 'POST' === $http_method && isset( $body_params['salesforce_object_type'] ) && isset( $body_params['salesforce_id'] ) ) {
					$result = $this->pull->manual_pull( $body_params['salesforce_object_type'], $body_params['salesforce_id'] );
				}
				break;
			case 'push':
				if ( ( 'POST' === $http_method || 'PUT' === $http_method || 'DELETE' === $http_method ) && isset( $body_params['wordpress_object_type'] ) && isset( $body_params['wordpress_id'] ) ) {
					$result = $this->push->manual_push( $body_params['wordpress_object_type'], $body_params['wordpress_id'], $http_method );
				}
				break;
		}

		return $result;
	}

}

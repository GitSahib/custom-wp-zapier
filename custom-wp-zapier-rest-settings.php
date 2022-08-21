<?php
namespace CustomWpZapier\Settings;
/**
 * 
 */
use WP_REST_Server;
use WP_REST_Request;
use CustomWpZapier\Utils\Utils;
use CustomWpZapier\Mappings\Mappings;
use CustomWpZapier\UserActivity\UserActivity;
use MyListing\Ext\Visits\Visits;
if ( ! function_exists( 'post_exists' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/post.php' );
}
if (! function_exists("current_user_can")){
	require_once (ABSPATH. 'wp-includes/capabilities.php');
}
class RestSettings
{
	private $slug = CUSTOM_WP_ZAPIER_SETTINGS_MAIN_PAGE; 
    private $user;
    private $debug = FALSE;
    private $api_meta_fields = array();
    private $api_schedule_fields = array();
    private $api_workhour_fields = array();
    private $api_taxonomy_fields = array();
    private $api_post_fields     = array();
    private $api_related_listings_fields = array();
	public function __construct()
	{
		add_action( 'rest_api_init', array($this, 'custom_wp_zappier_rest_api_init'), 10, 0);
		$this->api_meta_fields = Mappings::api_meta_fields();
	    $this->api_schedule_fields = Mappings::api_schedule_fields();
	    $this->api_workhour_fields = Mappings::api_workhour_fields();
	    $this->api_taxonomy_fields = Mappings::api_taxonomy_fields();
	    $this->api_post_fields     = Mappings::api_post_fields();
	    $this->api_related_listings_fields = Mappings::api_related_listings_fields();
	}


	public function custom_wp_zappier_rest_api_init()
	{

	    $namespace = 'custom-wp-zapier/v1';
	    //public api verified via security_key(GUID) since they won't send nonce
	    //see save_sf_post where it checks if security_key was empty or did not match
	    register_rest_route( $namespace,
	        '/sf-post-hook',
	        array( 
	            array(
	                'methods' => WP_REST_Server::CREATABLE,
	                'callback' => array($this, 'save_sf_post'),
	                'permission_callback' => [$this, 'check_security_key']
	            )
	        )
	    );
	    //admin api verified by nonce, see dataService.js and custom-wp-zapier-settings.php localization part
	    register_rest_route( $namespace,
	        '/save-settings',
	        array( 
	            array(
	                'methods' => WP_REST_Server::CREATABLE,
	                'callback' => array($this, 'save_settings'),
	                'permission_callback' => [$this, 'check_nonce']
	            )
	        )
	    );
	    //admin api verified by nonce, see dataService.js and custom-wp-zapier-settings.php localization part
		register_rest_route( $namespace,
	        '/get-mappings',
	        array( 
	            array(
	                'methods' => WP_REST_Server::READABLE,
	                'callback' => array($this, 'get_mappings'),
	                'permission_callback' => [$this, 'check_nonce']
	            )
	        )
	    );

	    register_rest_route( $namespace,
	        '/save-mapping',
	        array( 
	            array(
	                'methods' => WP_REST_Server::DELETABLE,
	                'callback' => array($this, 'delete_mapping'),
	                'args' => ['ApiFieldName', 'Type'],
	                'permission_callback' => [$this, 'check_nonce']
	            )
	        )
	    );

	    register_rest_route( $namespace,
	        '/save-mapping',
	        array( 
	            array(
	                'methods' => WP_REST_Server::CREATABLE,
	                'callback' => array($this, 'save_mapping'),
	                'args' => ['ApiFieldName', 'WpFieldName', 'Type'],
	                'permission_callback' => [$this, 'check_nonce']
	            )
	        )
	    );

	    register_rest_route( $namespace,
	        '/user-insights',
	        array( 
	            array(
	                'methods' => WP_REST_Server::READABLE,
	                'callback' => array($this, 'user_insights'),
	                'permission_callback' => [$this, 'check_security_key']
	            )
	        )
	    );

	    register_rest_route( $namespace,
	        '/user-activity',
	        array( 
	            array(
	                'methods' => WP_REST_Server::READABLE,
	                'callback' => array($this, 'user_activity'),
	                'permission_callback' => [$this, 'check_security_key']
	            )
	        )
	    );

	    register_rest_route( $namespace,
	        '/user-list',
	        array( 
	            array(
	                'methods' => WP_REST_Server::READABLE,
	                'callback' => array($this, 'user_list'),
	                'permission_callback' => [$this, 'check_security_key']
	            )
	        )
	    );

	    register_rest_route( $namespace,
	        '/listing-stats',
	        array( 
	            array(
	                'methods' => WP_REST_Server::READABLE,
	                'callback' => array($this, 'listing_stats'),
	                'permission_callback' => [$this, 'check_security_key']
	            )
	        )
	    );
	}	
	
	public function check_nonce(WP_REST_Request $request)
	{
        return wp_verify_nonce($request->get_header('x_wp_nonce'), 'wp_rest') && 
        	   current_user_can( 'manage_options' );
    }

    public function check_security_key(WP_REST_Request $request)
	{
		$params = Utils::sanitize_post_values(['security_key' => '']);
		if(empty($params))
		{
			$params = Utils::sanitize_header_values(['Apikey' => '']);			
			$params['security_key'] = isset($params['Apikey']) ? $params['Apikey'] : "";
		}
		
		if(!isset($params['security_key']))
		{
			return FALSE;
		}

		$settings = (array)get_option(CUSTOM_WP_ZAPIER_SETTINGS_GROUP);
		if(empty($settings['security_key']))
		{
			return FALSE;
		}
        return $settings['security_key'] === $params['security_key'];
    }

    public function user_insights()
    {
    	$options = Utils::sanitize_get_values([
    			'order' => '',
    			'orderby' => '',
				'page' => '',
				'users_per_page' => ''
		]);
		$activity = new UserActivity(null);
    	return rest_ensure_response($activity->get_insights($options));
    }

    public function user_activity()
    {
    	$args = Utils::sanitize_get_values(['user_id' => '']);
    	if(!isset($args['user_id']))
    	{
    		return rest_ensure_response([
    			'Status' => "Bad Request",
    			'Messages' => 'user_id was not provided'
    		]);
    	}
    	$activity = new UserActivity($args['user_id']);
    	return rest_ensure_response($activity->get_activity());
    }

    public function user_list()
    {
    	$activity = new UserActivity(null);
    	return rest_ensure_response($activity->get_users());
    }

    public function listing_stats()
    {
    	$args = Utils::sanitize_get_values([
    		'user_id' => '',
    		'listing_id' => '',   		
    	]);
    	$visits = new Visits();
    	return rest_ensure_response($visits->get_grouped_stats($args));
    }

	public function save_settings(WP_REST_Request $request)
    { 
    	$request = Utils::sanitize_post_values(['security_key' => '', 'mappings' => '']);
        
        $response = array(
            'Status' => 1,
            'Messages' => [],
            'DataRecieved' => $request
        );

        $settings = get_option(CUSTOM_WP_ZAPIER_SETTINGS_GROUP);

        if(!empty($request['security_key']))
        {            
            $settings['security_key'] = $request['security_key'];
        }
        
        if(!empty($request['security_key']))
        {
        	update_option(CUSTOM_WP_ZAPIER_SETTINGS_GROUP, $settings);
        }

    	return rest_ensure_response($response);
    }

    public function get_mappings()
    { 
        $response = array(
            'Status' => 1,
            'Messages' => []
        );
        
        $settings = get_option(CUSTOM_WP_ZAPIER_SETTINGS_GROUP);
        if(!isset($settings['Mappings']))
        {
            $settings['Mappings'] = [
        		'taxonomy' => Mappings::api_taxonomy_fields(),
        		'meta' => array_merge(
        				Mappings::api_meta_fields(), 
        				Mappings::api_workhour_fields()
        		),
        		'post' => Mappings::api_post_fields(),
        		'schedule' => Mappings::api_schedule_fields()
        	];
        	update_option(CUSTOM_WP_ZAPIER_SETTINGS_GROUP, $settings);
        }
        $response['Mappings'] = $settings['Mappings'];
    	return rest_ensure_response($response);
    }

    public function save_mapping(WP_REST_Request $request)
    {
    	$request = Utils::sanitize_post_values([
    		'ApiFieldName' => '', 
    		'WpFieldName' => '',
    		'Type' => ''
    	]);
    	$response = array(
            'Status' => 1,
            'Messages' => [],
            'DataRecieved' => $request
        );
        $settings = get_option(CUSTOM_WP_ZAPIER_SETTINGS_GROUP);
        $mappings = $settings['Mappings'];
        if(!isset($mappings[$request['Type']]))
        {
        	$mappings[$request['Type']] = [];
        }
        $mappings[$request['Type']][$request['ApiFieldName']] = $request['WpFieldName'];
        $settings['Mappings'] = $mappings;
        update_option(CUSTOM_WP_ZAPIER_SETTINGS_GROUP, $settings);
        return rest_ensure_response($response);
    }
    
    public function delete_mapping(WP_REST_Request $request)
    {
    	$request = Utils::sanitize_get_values([
    		'ApiFieldName' => '',
    		'Type' => ''
    	]);

    	$response = array(
            'Status' => 1,
            'Messages' => [],
            'DataRecieved' => $request
        );
        $settings = get_option(CUSTOM_WP_ZAPIER_SETTINGS_GROUP);
        $mappings = $settings['Mappings'];
        unset($mappings[$request['Type']][$request['ApiFieldName']]);
        $settings['Mappings'] = $mappings;
        update_option(CUSTOM_WP_ZAPIER_SETTINGS_GROUP, $settings);
        return rest_ensure_response($response);
    }

    public function save_sf_post()
    {  
        $request = Utils::sanitize_post_values( $this->get_mapped_fields() );        
        $response = array(
            'Status' => 1,
            'Messages' => [],
            'DataRecieved' => $request
        );        
        $post_id = $this->save_or_update_post($request, $response);
        //stop processing if no post was found or created
        if(empty($post_id))
        {
			return rest_ensure_response($response); 
		}
		$this->save_post_meta($post_id, $request, $response);
		$this->save_work_hours($post_id, $request, $response);
		$this->save_schedule($post_id, $request, $response);
        $this->save_taxonomy($post_id, $request, $response);
        $this->save_related_listings($post_id, $request, $response);
        $this->code_address($post_id, $request, $response);
        return rest_ensure_response($response);
    }

    private function get_mapped_fields()
	{
		return array_merge(
			$this->api_post_fields,
			$this->api_meta_fields,
			$this->api_taxonomy_fields,
			$this->api_schedule_fields,
			$this->api_workhour_fields,			
			$this->api_related_listings_fields
		);
	}

	private function save_or_update_post($request, &$response)
	{
		global $wpdb;
		$post_status = "draft";
        $post_author = "";
        $post_content = "";
		
		if( empty($request['Request_ID_18_Digit']) )
		{
			$response['Messages'][] = 'Not doing anything, Request Id was not supplied.';
			return "";
		}

		$api_meta_fields = $this->api_meta_fields;
		$meta_key = $api_meta_fields['Request_ID_18_Digit'];
		$meta_value = $request['Request_ID_18_Digit'];
		$listing_type = isset($request['Listing_Type__c']) ? $request['Listing_Type__c'] : "Deals";

		$post_sql = $wpdb->prepare("
			SELECT p.id 
			FROM $wpdb->posts p
			INNER JOIN $wpdb->postmeta pm ON p.id = pm.post_id
			WHERE pm.meta_key = %s AND pm.meta_value = %s AND p.post_status != 'trash'",
			$meta_key, $meta_value
		);

		$listing_type_sql = $wpdb->prepare("
			SELECT p.post_name 
			FROM $wpdb->posts p
			WHERE p.post_type = 'case27_listing_type' AND p.post_title = %s",
			$listing_type
		);
		
		$this->debugSQL($response, $post_sql);
		$this->debugSQL($response, $listing_type_sql);
        //get the post id
        $post_id = $wpdb->get_var($post_sql);
        //get listing type
        $listing_type = $wpdb->get_var($listing_type_sql);
        
        //we can't create a post the empty title
        if(empty($post_id) && empty($request['Ad_Title__c']))
        {
        	$response['Messages'][] = 'Not doing anything, deal title was not supplied.';
			return ""; 
		}

        //try finding the post author
        if(!empty($request['Account_Wordpress_User__c']))
        {
        	$post_author = $request['Account_Wordpress_User__c'];
        	$user_sql = $wpdb->prepare("
	        	SELECT id FROM $wpdb->users WHERE user_login = %s
	        ", $post_author);
	        //get the post author
        	$post_author = $wpdb->get_var($user_sql);
        }
       	
       	if(!empty($request['Ad_Details__c']))
       	{
       		$post_content = $request['Ad_Details__c'];
       	}

       	//validate the post status
       	$should_update_status = FALSE;	
       	if(!empty($request['Request_Status__c']))
        {
        	$post_status = $request['Request_Status__c'];
        }
       	
		if(!in_array(strtolower($post_status), Mappings::post_statuses()))
		{
			$post_status = 'draft';
		}
		else
		{
			$should_update_status = TRUE;
		}

		if (empty($post_id)) {
			$response['Messages'][] = 'Will insert new deal';
		    $post_id = wp_insert_post(array(
		        'post_type'  => 'job_listing',
		        'post_title' => $request['Ad_Title__c'],
		        'post_status' => $post_status,
		        'post_content' => $post_content,
		        'post_author' => $post_author
		    ));
		    add_post_meta($post_id, '_case27_listing_type' , empty($listing_type) ? 'deals' : $listing_type);
		    $response['Messages'][] = "New deal id $post_id with author $post_author";
		    $should_update_status = FALSE;
		}
		else
		{
			$response['Messages'][] = "Deal found with id $post_id";	
		}
		
		//this should not happen, but lets guess if the post_insert fails
		if(empty($post_id)){
			return ""; 
		}

		//this should only happen if we found a post already and the status is different
		if($should_update_status)
		{
			$response['Messages'][] = "Updating status of post $post_id";
			wp_update_post(['ID' => $post_id, 'post_status' => $post_status]);	
		}
		return $post_id;
	}

    private function save_or_update_post_meta($post_id, $meta_key, $meta_value)
    {
    	$meta = get_post_meta($post_id, $meta_key, true);
			
		if(!isset($meta))
		{
			add_post_meta($post_id, $meta_key , $meta_value );
		}
		else
		{
			update_post_meta($post_id, $meta_key , $meta_value );
		}

    }

    private function code_address($post_id, $request, &$response)
    {
    	if(empty($request['Location__c']))
    	{
    		$response['Messages'][] = "Address not provided, will not geocode";
    		return;
    	}

		$map_options = get_option("mylisting_maps");
		if(!empty($map_options) && is_string($map_options))
		{
			$map_options = json_decode($map_options);
		}
		
		if(empty($map_options) || empty($map_options->gmaps_api_key))
		{
			$response['Messages'][] = "gmaps_api_key not found, will not geocode";
			return;
		}
		
		$latlng = Utils::code_address($map_options->gmaps_api_key, $request['Location__c']);
		
		if(empty($latlng))
		{
			$response['Messages'][] = "Could not code address, will not save lat lng";
			return;
		}

		$this->save_or_update_post_meta($post_id, "geolocation_lat", $latlng['lat']);
    	$this->save_or_update_post_meta($post_id, "geolocation_long", $latlng['lng']);
    	$response['Messages'][] = "Updated lat long for $post_id ".json_encode($latlng);
    }

    private function save_post_meta($post_id, $request, &$response)
    {
    	$api_meta_fields = $this->api_meta_fields;
    	$url_fields   = ['Wordpress_Account_Listing_Id__c', 'Menu__c', 'Website__c'];
    	$date_fields  = ['Promotion_Expiration_Date__c'];
    	$array_fields = ['Wordpress_Banner_URL_from_Account__c'];
    	$int_fields   = ['Priority__c'];
    	foreach ($api_meta_fields as $f => $m) 
    	{
    		//if no mapping is found or the field is not posted then get back
    		if(empty($m) || !isset($request[$f]) || $m == "_case27_listing_type")
    		{
    			continue;
    		}
    		//if it is an int field and is not numeric, get back but inform user
    		if(in_array($f, $int_fields) && !is_numeric($request[$f]))
    		{
				$message = 'Invalid deal meta '. 
    						$api_meta_fields[$f] ." = ". 
    						(is_string($request[$f])? $request[$f] : json_encode($request[$f]));
    			$response['Messages'][] = $message;
    			continue;
    		}
    		//empty will return true for '0' so checking both
    		if(!in_array($f, $int_fields) && empty($request[$f]))
			{
				continue;				
			}

    		if(in_array($f, $url_fields))
    		{
    			$request[$f] = Utils::get_url($request[$f]);
    		}

    		if(in_array($f, $date_fields) && Utils::is_valid_date($request[$f]))
			{
				$request[$f] = Utils::utc_date_to_my_sql($request[$f]);
			}
 	
 			if(in_array($f, $array_fields))
 			{
 				$request[$f] = array_map(function($field){ 
 					return trim($field);
 				}, explode(",", $request[$f]));
 			}

			$this->save_or_update_post_meta($post_id, $api_meta_fields[$f] , $request[$f]);			

			$response['Messages'][] = 'Inserted deal meta '. $api_meta_fields[$f] ." = ". (is_string($request[$f])? $request[$f] : json_encode($request[$f]));
		}
    }

    private function save_taxonomy($post_id, $request, &$response){
    	global $wpdb;
    	$api_taxonomy_fields = $this->api_taxonomy_fields;    	
    	$termsTaxanonmySqls = [];
    	$terms = [];
    	$taxonomy = [];
    	foreach ($api_taxonomy_fields as $f => $m)
    	{
			
			if(empty($m) || empty($request[$f]))
			{
				continue;
			}
			//debug variables
			$terms[] = $request[$f];
			$taxonomy[] = $m;
			//get array of terms
			$provided_terms = explode(",", $request[$f]);
			//lets trim the individual terms
			$provided_terms = array_map(function($term){
				return trim($term);
			}, $provided_terms);
			//create a placeholder foreach term
			$placeholders = implode(', ', array_fill(0, count($provided_terms), '%s'));
			//add this prepared statement to the list of sql statements
			$termsTaxanonmySqls[] = 
			call_user_func_array(array($wpdb, 'prepare'), array_merge(
				array(
					"
					SELECT 
						%d,
						term_taxonomy_id,
						1
					FROM $wpdb->term_taxonomy 
					WHERE taxonomy = %s AND term_id IN (
						SELECT term_id FROM $wpdb->terms WHERE name IN($placeholders)
					)", 
					$post_id, 
					$m
				), $provided_terms)
			);				
		}

		$this->debugSQL($response, $termsTaxanonmySqls);

		$finalSQL = "
			REPLACE INTO $wpdb->term_relationships(object_id, term_taxonomy_id, term_order)
		";

		if(empty($termsTaxanonmySqls))
		{
			$response["Messages"][] = 'No taxonomy was found in the request.';
			return;
		}
		
		$finalSQL .= implode("\nUNION\n", $termsTaxanonmySqls);		
		
		$this->debugSQL($response, $finalSQL);

		$wpdb->query($finalSQL);
		
		$message = "Added taxonomy:".implode(", ", $taxonomy). " terms:".implode(", ", $terms);

		$response["Messages"][] = $message;
    }
	
	private function save_related_listings($post_id, $request, &$response)
    {
    	global $wpdb;
    	if(!empty($request['Account_Name__c']))
    	{
    		$post_title = $request['Account_Name__c'];
    		$meta_key = '_case27_listing_type';
    		$meta_value = 'dispensaries';
    		//find parent listing id
    		$sql = $wpdb->prepare("
    			SELECT p.id as listing_id 
    			FROM $wpdb->posts p
    			INNER JOIN $wpdb->postmeta pm ON p.id = pm.post_id
    			WHERE pm.meta_key = %s AND pm.meta_value = %s AND p.post_title = %s",
    			$meta_key, $meta_value, $post_title
    		);
    		$parent_listing_id = $wpdb->get_var($sql);
    		if(empty($parent_listing_id))
    		{
    			$response['Messages'][] = "No retailer-deals found for $post_id";
    			return;
    		}
    		//parent listing was found lets add a record in relations table
    		$listing_relations = $wpdb->prefix."mylisting_relations";
    		$field_key = 'retailer-deals';
    		//check if the relation already exists then bail out
    		$sql = $wpdb->prepare("SELECT 1 
    				FROM $listing_relations 
    				WHERE parent_listing_id = %d
    					AND child_listing_id = %d
    					AND field_key = %s",
    					$parent_listing_id, $post_id, $field_key
    				);
    		$exists = $wpdb->get_var($sql);
    		if(!empty($exists))
    		{
    			$response['Messages'][] = "retailer-deals exists for $post_id";
    			return;
    		}
    		//get the item order based on the post_id which is child_listing_id
    		$sql = $wpdb->prepare("
    				SELECT MAX(item_order) AS item_order
    				FROM $listing_relations 
    				WHERE  child_listing_id = %d AND field_key = %s",
    					$post_id, $field_key
    		);
    		$item_order = $wpdb->get_var($sql);
    		if(empty($item_order))
    		{
    			$item_order = 0;
    		}
    		else
    		{
    			$item_order += 1;
    		}
    		//insert the relationship
    		$sql = $wpdb->prepare("
	    		INSERT INTO $listing_relations(
	    			parent_listing_id, 
	    			child_listing_id, 
	    			field_key, 
	    			item_order
	    		) 
	    		VALUES(%d, %d, %s, %d)",
	    		$parent_listing_id, $post_id, $field_key, $item_order
	    	);
    		$wpdb->query($sql);
    		$response['Messages'][] = "Inserted retailer-deals for $post_id item_order $item_order";
    	}
    }

    private function read_frequencies($frequency)
    {
    	$frequencies = explode(",", $frequency);
		//lets trim the individual frequencies
		$frequencies = array_map(function($f){
			return trim($f);
		}, $frequencies);		
		//return valid ones
		$frequencies = array_filter($frequencies, function($f){
			return is_numeric($f);
		});
		return $frequencies;
    }

    private function read_units($units)
    {
    	$units = explode(",", $units);
    	//lets trim the individual units and signularize them
    	$units = array_map(function($u){
    		return strtoupper(Utils::singularize(trim($u)));
    	}, $units);    	
    	//return valid ones
    	$units = array_filter($units, function($u){
			return Utils::is_valid_date_unit($u);
		});
		return $units;
    }

    private function read_dates($dates)
    {
    	$dates = explode(",", $dates);
		//lets trim the individual frequencies
		$dates = array_map(function($f){
			return trim($f);
		}, $dates);
		//return valid ones
		$dates = array_filter($dates, function($d){
			return Utils::is_valid_date($d);
		});
		//map to mysql dates
		$dates = array_map(function($d){
			return  Utils::utc_date_to_my_sql($d);
		}, $dates);
		
		return $dates;
    }

    private function save_schedule($post_id, $request, &$response)
    {
    	global $wpdb;
    	$fields = $this->api_schedule_fields;
    	$event = [];

    	foreach($fields as $f => $m)
    	{
    		if(empty($request[$f]) || empty($m))
    		{
    			continue;
    		}
    		switch ($m) 
    		{
    			case 'frequency':
    				$frequencies = $this->read_frequencies($request[$f]);
    				if(!empty($frequencies))
    				{
    					$event[$m] = $frequencies;
    				}
    				else
    				{
    					$response['Messages'][] = "Invalid frequency ".$request[$f];
    				}
    				break;
    			case 'repeat_unit':
    				$units = $this->read_units($request[$f]);
    				if(!empty($units))
    				{
    					$event[$m] = $units;
    				}
    				else
    				{
    					$response['Messages'][] = "Invalid unit ".$request[$f];
    				}
    				break;
    			default:
    				$dates = $this->read_dates($request[$f]);
    				if(!empty($dates))
    				{
    					$event[$m] = $dates;
    				}
    				else
    				{
    					$response['Messages'][] = "Invalid $f dates ".$request[$f];
    				}				
    				break;
    		}
    	}

    	if(empty($event))
    	{    		
    		return;    		
    	}
    	
    	//delete old events for this listing
    	$events_table = $wpdb->prefix."mylisting_events";
    	$event_sql = $wpdb->prepare("
    		DELETE FROM $events_table WHERE listing_id = %d
    	", $post_id);
    	$wpdb->query($event_sql);

    	foreach ($event['start_date'] as $key => $value) {
    		$e = [];
    		$e['listing_id'] = $post_id;
			$e['field_key'] = 'deal-dates';
			if(empty($event['repeat_unit'][$key]))
			{
				$e['repeat_unit'] = "NONE";
			}
			else
			{
				$e['repeat_unit'] = $event['repeat_unit'][$key];
			}
			if(empty($event['start_date'][$key]))
			{
				$response['Messages'][] = "Empty start_date not allowed";
				return;
			}
			$e['start_date'] = $event['start_date'][$key];
			if(empty($event['end_date'][$key]))
			{
				$response['Messages'][] = "Empty end_date not allowed";
				return;
			}
			$e['end_date'] = $event['end_date'][$key];
			if(empty($event['repeat_end'][$key]))
			{
				$response['Messages'][] = "Empty repeat_until not allowed";
				return;
			}
			$e['repeat_end'] = $event['repeat_end'][$key];
			if(empty($event['frequency'][$key]))
			{
				$response['Messages'][] = "Empty frequency not allowed";
				return;
			}
			$e['frequency'] = $event['frequency'][$key];
			$wpdb->insert($events_table, $e);
			$response['Messages'][] = "Inserted event schedule. ".json_encode($e);
    	}
    }

    private function save_work_hours($post_id, $request, &$response){
    	
    	$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    	$timezone = '';    	
    	$work_hours = get_post_meta($post_id, '_work_hours', true);

    	$work_hours = (array)$work_hours;
    	if(empty($work_hours['Monday']))
    	{
    		$work_hours = [];
    		foreach($days as $day)
    		{
    			$work_hours[$day] = 
    			[
    				'status' => 'closed-all-day'
    			];
    		}
    		$work_hours["timezone"] = 'America/Los_Angeles';
    		add_post_meta($post_id, '_work_hours', $work_hours);
    		$response['Messages'][] = "Added work_hours: ".json_encode($work_hours);
    	}

    	$should_update_hours = FALSE;
    	
    	if(!empty($request['Timezone__c']))
    	{
    		$timezone = $request['Timezone__c'];
    	}

    	if(Utils::is_valid_time_zone($timezone))
    	{
    		$work_hours['timezone'] = $timezone;
    		$should_update_hours = TRUE;
    	}
    	else if(!empty($timezone))
    	{
    		$response['Messages'][] = "Invalid timezone $timezone";
    	}

    	foreach($days as $day)
    	{
    		$status = "";
    		$duration = array();
    		$open_time = "";
    		$close_time = "";
    		if(!empty($request[$day."_Open__c"]))
    		{
    			$open_time = $request[$day."_Open__c"];
    		}
    		if(!empty($request[$day."_Close__c"]))
    		{
    			$close_time = $request[$day."_Close__c"];
    		}
    		if(Utils::is_valid_time($open_time))
    		{
    			$status = "enter-hours";
    			$duration["from"] = Utils::am_pm_to_24($request[$day."_Open__c"]);
    		}
    		else
    		{
    			$response['Messages'][] = "Invalid open_time for $day $open_time";
    		}
    		if(Utils::is_valid_time($close_time))
    		{
    			$status = "enter-hours";
    			$duration["to"] = Utils::am_pm_to_24($request[$day."_Close__c"]);
    		}
    		else
    		{
    			$response['Messages'][] = "Invalid close_time for $day $close_time";
    		}
    		
    		if(empty($duration))
    		{
    			continue;
    		}

    		$work_hours[$day]["status"] = $status;
    		$work_hours[$day]["0"] = $duration;
    		$should_update_hours = TRUE;
    	}

    	if($should_update_hours === TRUE)
    	{
    		update_post_meta($post_id,'_work_hours',  $work_hours);
    		$response['Messages'][] = "Update work_hours: ".json_encode($work_hours);
    	}
    	
    }    

    private function debugSQL(&$response, $sql)
    {
    	if($this->debug === TRUE)
		{
			array_push($response['Messages'], preg_replace("/\s+/", " ", $sql));
		}
    }

}
new RestSettings();
<?php

/**
 * Retrieves the general user activity, such as posts created and comments posted
 */
namespace CustomWpZapier\UserActivity;
use CustomWpZapier\UserActivity\UserActivity_Helper;
use CustomWpZapier\UserActivity\UserActivity_User_Query;
class UserActivity{

	const MAX_ITEMS_TO_LOAD = 5;
	protected $user_id;	
	public function __construct($user_id){
		$this->user_id = $user_id;
	}
	
	protected function get_table_name()
	{
		return UserActivity_Helper::user_data_db_table;
	}

	protected function table_exists($table_name)
	{
		global $wpdb;
		if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
		    return FALSE;
		}
		return TRUE;
	}

	public function get_users($options = [])
	{
		$number = isset($options['users_per_page']) ? $options['users_per_page'] : 20;
		$offset = 0;
		$fields = array( 'user_login', 'user_email', 'user_nicename', 'display_name', 'user_registered' );
		if(isset($options['page']) && $options['page'] != 1)
		{
			$offset = $this->get_query_offset($options['page'], $number);
		}
		add_action( 'pre_user_query', [$this, 'add_meta_query'] );
		$user_query = new \WP_User_Query( array( 'number' => $number, 'offset' => $offset, 'fields' => $fields ) );
		remove_action( 'pre_user_query', [$this, 'add_meta_query'] ); 
		return $user_query->results;
	}

	function add_meta_query( $query ) {
	    global $wpdb;
	    
	    //let's add the billing_coutry to our meta fields in our query
	    $query->query_fields .= ",
	    (SELECT meta_value FROM wp_usermeta WHERE meta_key = 'first_name' AND user_id = wp_users.ID) AS first_name,
	    (SELECT meta_value FROM wp_usermeta WHERE meta_key = 'last_name' AND user_id = wp_users.ID) AS last_name,
	    user_registered,
	    (SELECT meta_value from wp_posts
		 INNER JOIN wp_usermeta ON CONCAT('af_c_f_additional_', wp_posts.ID) = wp_usermeta.meta_key 
		 WHERE post_type = 'af_c_fields' and post_name = 'birthdate' AND wp_usermeta.user_id = wp_users.ID
		) AS birth_date,
		(SELECT CONCAT('https://www.honeypottt.com/wp-content/uploads/addify-custom-fields/', meta_value) from wp_posts
		  INNER JOIN wp_usermeta ON CONCAT('af_c_f_additional_', wp_posts.ID) = wp_usermeta.meta_key 
		  WHERE post_type = 'af_c_fields' and post_name = 'upload-id' AND wp_usermeta.user_id = wp_users.ID
		  ) AS upload_id,
		(SELECT meta_value from wp_posts
		   INNER JOIN wp_usermeta ON CONCAT('af_c_f_additional_', wp_posts.ID) = wp_usermeta.meta_key 
		   WHERE post_type = 'af_c_fields' and post_name = 'zip-code' AND wp_usermeta.user_id = wp_users.ID
		) AS zip_code";
	}

	public function get_insights($options = []){
		$args = array(
			'number' => isset($options['users_per_page']) ? $options['users_per_page'] : 20,
			'orderby' => isset($options['orderby']) ? $options['orderby'] : "last_seen",
			'order' => isset($options['order']) ? $options['order'] : "DESC",
		);

		if(isset($options['page']) && $options['page']!=1){
			$args['offset'] = $this->get_query_offset($options['page'], $options['number']);
		}

		$user_query = new UserActivity_User_Query($args, null);
		$fields = [
		    "username",
		    "user_groups",
		    "email",
		    "registered",
		    "role",
		    "posts",
		    "last_seen",
		    "sessions",
		    "browser",
		    "country",
		    "city"
		];
		$users = $user_query->get_users($fields);
		return $users;
	}

	public function get_activity(){
		$activity = array();

		$exclude_post_types = array('revision', 'attachment', 'nav_menu_item');
		$allowed_post_types = UserActivity_Helper::get_allowed_post_types();

		foreach ($allowed_post_types as $post_type_name) {
			$post_type = get_post_type_object( $post_type_name );
			if(!in_array($post_type, $exclude_post_types)){

				$post_activity = $this->get_post_activity($post_type);
				if(!empty($post_activity)){
					$activity[]=$post_activity;
				}

				$comment_activity = $this->get_comment_activity($post_type);
				if(!empty($comment_activity)){
					$activity[]=$comment_activity;
				}
			}
		}

		return apply_filters('usin_user_activity', $activity, $this->user_id);
	}

	protected function get_post_activity($post_type){
		$args = array(
			'author'=>$this->user_id, 
			'post_type'=>$post_type->name, 
			'posts_per_page'=>self::MAX_ITEMS_TO_LOAD, 
			'orderby'=>'date', 
			'order'=>'desc', 
			'post_status'=> UserActivity_Helper::get_allowed_post_statuses()
		);

		$suppress_filters = apply_filters("usin_suppress_filters_$post_type->name", false);
		if($suppress_filters){
			$args['suppress_filters'] = true;
		}

		$query = new \WP_Query($args);

		$count = $query->found_posts;
		if($count){
			
			$list = array();

			foreach ($query->posts as $post) {
				$post_title = $post->post_title;
				if($post->post_status != 'publish'){
					$status = get_post_status_object($post->post_status);
					if(isset($status->label)){
						$post_title .= " ($status->label)";
					}
				}
				$list[]=array('title'=>$post_title, 'date_created' => $post->post_date, 'link'=>get_permalink($post->ID));
			}

			return array(
				'type' => 'post_type_'.$post_type->name,
				'label' => $count == 1 ? $post_type->labels->singular_name : $post_type->labels->name,
				'count' => $count,
				'link' => admin_url('edit.php?post_type='.$post_type->name.'&usin_user='.$this->user_id.'&usin_post_type='.$post_type->name),
				'list' => $list
			);

		}
	}


	protected function get_comment_activity($post_type){
		$count = get_comments(array('user_id'=>$this->user_id, 'post_type'=>$post_type->name, 'count'=>true));

		if($count){
			$label = $post_type->labels->singular_name.' ';
			$label .= $count == 1 ? __('Comment', 'usin') : __('Comments' , 'usin');

			$list = array();

			$com_args = array(
				'user_id'=>$this->user_id,
				'post_type'=>$post_type->name,
				'number'=>self::MAX_ITEMS_TO_LOAD,
				'orderby'=>'date',
				'order'=>'DESC'
			);

			$exclude_comment_types = UserActivity_Helper::get_exclude_comment_types();
			if(!empty($exclude_comment_types)){
				$com_args['type__not_in'] = $exclude_comment_types;
			}
			
			$comments = get_comments($com_args);
			foreach ($comments as $comment) {
				$content = wp_html_excerpt( $comment->comment_content, 40, ' [...]');
				$list[]=array('title'=>$content, 'date_created' => $comment->comment_date, 'link'=>get_permalink($comment->comment_post_ID));
			}

			return array(
				'type' => 'comment_'.$post_type->name,
				'for' => $post_type->name,
				'label' =>  $label,
				'count' => $count,
				'link' => admin_url('edit-comments.php?usin_user='.$this->user_id.'&usin_post_type='.$post_type->name),
				'list' => $list
			);
		}
	}

	protected function get_query_offset($page, $items_per_page){
		return ($page - 1) * $items_per_page;
	}
}
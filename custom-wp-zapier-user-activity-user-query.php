<?php
namespace CustomWpZapier\UserActivity;
use CustomWpZapier\UserActivity\UserActivity_User;
class UserActivity_User_Query extends UserActivity_Query {

	public function get_users($default_fields = null) {
		global $wpdb;

		$this->build_query($default_fields);

		ob_start();
		$start_time = microtime(true);
		$wpdb->show_errors();

		$results = $wpdb->get_results($this->query);

		$total = $wpdb->get_var('SELECT FOUND_ROWS()');
		$results = apply_filters('usin_users_raw_data', $results);

		$wpdb->hide_errors();
		$error = ob_get_clean();

		if (!empty($error)) {
			$error .= sprintf("%s: %fs", __('Execution time', 'usin'), round((microtime(true) - $start_time), 5));
			return new WP_Error('usin_db_error', __('Database error', 'usin'), $error);
		}

		$users = $this->db_rows_to_objects($results);
		return array('users' => $users, 'total' => $total, 'alltotal' => $this->get_total_users());
	}

	public function get_user($user_id) {
		global $wpdb;

		ob_start();
		$start_time = microtime(true);
		$wpdb->show_errors();

		$filter = new stdClass();
		$filter->by = 'ID';
		$filter->operator = 'equals';
		$filter->condition = $user_id;
		$filter->type = 'number';

		$this->filters = array($filter);

		$general_fields = usin_options()->get_field_ids_by_field_type('general');
		$personal_fields = usin_options()->get_field_ids_by_field_type('personal');
		$additional_fields = array('coordinates');
		$all_fields = array_merge($general_fields, $personal_fields, $additional_fields);
		$all_fields = apply_filters('usin_single_user_query_fields', $all_fields);

		$this->build_query($all_fields);

		$db_user = $wpdb->get_row($this->query);

		$wpdb->hide_errors();
		$error = ob_get_clean();

		if (!empty($error)) {
			$error .= sprintf("%s: %fs", __('Execution time', 'usin'), round((microtime(true) - $start_time), 5));
			return new WP_Error('usin_db_error', __('Database error', 'usin'), $error);
		}

		if (!empty($db_user)) {
			$db_user = apply_filters('usin_single_user_db_data', $db_user);

			return new UserActivity_User($db_user);
		}
	}


	public function build_query($default_fields = null) {
		global $wpdb;

		$args = $this->args;
		$filters = $this->filters;

		$this->set_query_select($default_fields);

		$this->set_filters();

		$this->set_query_order();

		$this->query .= $this->query_select;
		$this->query .= $this->get_query_joins();

		$this->set_conditions();


		$this->query .= $this->query_order;

		//set a limit
		if (isset($args['number']) && $args['number']) {
			if (isset($args['offset'])) {
				$this->query .= $wpdb->prepare(" LIMIT %d, %d", $args['offset'], $args['number']);
			} else {
				$this->query .= $wpdb->prepare(" LIMIT %d", $args['number']);
			}
		}
	}

	private function get_total_users() {
		global $wpdb;
		$query = "SELECT COUNT(distinct u.ID) FROM $wpdb->users AS u";
		if (is_multisite()) {
			$blog_id = $GLOBALS['blog_id'];
			if ($blog_id) {
				$key = $wpdb->get_blog_prefix($blog_id) . 'capabilities';
				$query .= $wpdb->prepare(" INNER JOIN $wpdb->usermeta AS m ON (u.ID = m.user_id AND m.meta_key = %s)", $key);
			}
		}
		$total = $wpdb->get_var($query);
		return apply_filters('usin_total_users', $total);
	}
}

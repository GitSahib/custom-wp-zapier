<?php
namespace CustomWpZapier\UserActivity;
use CustomWpZapier\UserActivity\UserActivity_Field_Defaults;
class UserActivity_Options{
	static $fields = NULL;
	protected $options = array(
		'users_per_page' => 20,
		'fields' => array(),
		'orderby' => 'registered',
		'order' => 'DESC',
		'field_order' => array(),
		'profile_settings' => null
	);
	static $visible_fields_cache = array();

	public function __construct(){
	}

	public function get($option_key, $default = null){
		switch ($option_key) {
			case 'fields':
				return self::get_fields();
				break;
			case 'users_per_page':
			case 'profile_settings':
				$val = $this->get_user_option($option_key);
				return $val ? $val : $this->options[$option_key];
				break;
			default:
				if($option_key == 'orderby' && usin_modules()->is_module_active('activity')){
					return 'last_seen';
				}
				if(isset($this->options[$option_key])){
					return $this->options[$option_key];
				}
				return $default;
				break;
		}
	}

	public function get_user_option($key){
		$user_id = get_current_user_id();
		return get_user_meta($user_id, 'usin_'.$key, true);
	}


	static function get_fields(){
		if(!isset(self::$fields)){
			//load the fields data
			$fields = self::get_default_fields();
			$fields = self::get_active_module_fields($fields);

			$user_id = get_current_user_id();
			$displayed_fields = get_user_meta($user_id, 'usin_fields', true);
			if(!empty($displayed_fields)){
				foreach ($fields as &$field){
					$field['show'] = in_array($field['id'], $displayed_fields);
				}
			}

			self::$fields = $fields;
		}

		return self::$fields;
	}
	
	public function get_ordered_fields($order = null){
		$fields = self::get_fields();
		
		if($order == null){
			$order = $this->get_user_option('field_order');
		}
		
		if(!empty($order)){
			$field_ids = wp_list_pluck($fields, 'id');
			usort($fields, array(new UserActivity_Options_Field_Sorter($order, $field_ids), 'sort'));
		}
		
		return $fields;
	}
	
	static function get_visible_fields(){
		$fields = self::get_fields();
		$visible_fields = array();
		foreach ($fields as $field ) {
			if(isset($field['show']) && $field['show']){
				$visible_fields[]=$field['id'];
			}
		}
		return $visible_fields;
	}

	/**
	 * Searches fields by filter type
	 * @param  string|array $types a type or a an array of types to search against
	 * @return array       The array of fields IDs that match the criteria
	 */
	static function get_field_ids_by_filter_type($types){
		$fields = self::get_fields();
		$fields_by_type = array();
		if(!is_array($types)){
			$types = array($types);
		}

		foreach ($fields as $field) {
			if(isset($field['filter']['type']) && in_array($field['filter']['type'], $types)){
				$fields_by_type[]=$field['id'];
			}
		}

		return $fields_by_type;
	}

	/**
	 * Searches fields by operator type - this is the "type" option in the field type
	 * declaration. For example, field with filter type "select" and "include_exclude" 
	 * are both "option" type of fields.
	 */
	static function get_field_ids_by_operator_type($type){
		$field_types = self::get_field_types_by_type($type);
		return self::get_field_ids_by_filter_type($field_types);
	}
	
	public function get_field_ids_by_field_type($type){
		$fields = self::get_fields();
		$fields_by_type = array();

		foreach ($fields as $field) {
			if(isset($field['fieldType']) && $field['fieldType'] == $type){
				$fields_by_type[]=$field['id'];
			}
		}

		return $fields_by_type;
	}
	
	static function get_field_types(){
		return UserActivity_Field_Defaults::get_field_types();
	}

	public function get_filter_operators(){
		$field_types = self::get_field_types();
		$operators = array();
		foreach ($field_types as $key => $options) {
			$operators[$key] = $options['operators'];
		}
		return $operators;
	}
	
	static function get_field_types_by_type($type){
		$field_types = self::get_field_types();
		$res = array();
		foreach ($field_types as $key => $options) {
			if(isset($options['type']) && $options['type'] === $type){
				$res[]=$key;
			}
		}
		
		return $res;
	}
	
	static function get_default_fields(){
		$fields = UserActivity_Field_Defaults::get_fields();
		$fields = apply_filters('usin_fields', $fields);

		return $fields;
	}
	
	/**
	 * Sets a default icon if it is missing.
	 * @param array $fields the fields to which to set the icons
	 */
	public function set_icons($fields){
		
		foreach ($fields as &$field ) {
			if(!isset($field['icon'])){
				$field['icon'] = isset($field['module']) ? $field['module'] : 'field';
			}
		}
		
		return $fields;
	}

	static function get_active_module_fields($fields){
		$active_fields = array();

		foreach ($fields as $key => $field) {
			if( !isset($field['module'])){
				$active_fields[]= $field;
			}
		}

		return $active_fields;
	}
	
	public function get_editable_fields(){
		$fields = self::get_fields();
		$editable_fields = array();
		
		foreach ($fields as $field) {
			if(!empty($field['editable'])){
				$editable_fields[]=$field['id'];
			}
		}
		return $editable_fields;
	}

	static function get_field_by_id($field_id){
		$fields = self::get_fields();
		
		foreach ($fields as $field) {
			if($field['id'] == $field_id){
				return $field;
			}
		}
	}
	
	static function is_field_visible($field_id){
		if(!isset(self::$visible_fields_cache[$field_id])){
			$field = self::get_field_by_id($field_id);
			$visible = (!empty($field) && isset($field['show'])) ? $field['show'] : false;
			self::$visible_fields_cache[$field_id] = $visible;
		}
		
		return self::$visible_fields_cache[$field_id];
	}

	public function update_user_option($key, $val){
		$user_id = get_current_user_id();
		if(array_key_exists($key, $this->options) && !empty($val) && $user_id){
			$res = update_user_meta( $user_id, 'usin_'.$key, $val );
			return (bool)$res;
		}

		return false;
	}

}

class UserActivity_Options_Field_Sorter{
    private $order;
    private $def_field_ids;

    function __construct( $order, $def_field_ids ) {
        $this->order = $order;
        $this->def_field_ids = $def_field_ids;
    }

    function sort( $a, $b ) {
		
		$a_index = array_search($a['id'], $this->order);
		$b_index = array_search($b['id'], $this->order);
		if($a_index === false){
			//the field doesn't exist in the order list
			//set a big index so it is displayed last
			$a_index = 10000 + $this->get_default_index($a['id']);
		}
		if($b_index === false){
			//the field doesn't exist in the order list
			//set a big index so it is displayed last
			$b_index = 10000 + $this->get_default_index($b['id']);
		}
		return ($a_index < $b_index) ? -1 : 1;
    }
	
	function get_default_index($field_id){
		return intval(array_search($field_id, $this->def_field_ids));
	}
}
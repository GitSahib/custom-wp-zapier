<?php
namespace CustomWpZapier\Settings;
use CustomWpZapier\Mappings\Mappings;
class Settings
{   
    private $is_ajax;
    /**
     * Menu slug
     *
     * @var string
     */
    protected $slug = CUSTOM_WP_ZAPIER_SETTINGS_MAIN_PAGE;
    /**
     * URL for assets
     *
     * @var string
     */
    protected $assets_url = CUSTOM_WP_ZAPIER_PLUGIN_DIR;
    /**
     * Apex_Menu constructor.
     *
     * @param string $assets_url URL for assets
     */
    /**
     * Start up
     */ 
    public function __construct($plugin_dir, $is_ajax = false)
    {               
        $this->is_ajax = $is_ajax;
        if(!$is_ajax){
            add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );
        } 
    }  

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            'Custom Wp Zapier', 
            'manage_options', 
            $this->slug, 
            array( $this, 'create_admin_page' )
        );
    }
    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        ?>
        <div class="wrap wp-custom-zapier-settings-wrap col-md-12">
            <div class="zapier-thinking">
                <span class="loader-text">Please Wait...</span>
                <span class="loader"></span>
            </div>
            <div class="body">
                <p class="text-center">We are listening to salesforce.</p>
                <div class="row">
                    <div class="col-md-6">
                        <?php $this->print_input('Security Key:', 'api_security_key_callback', '', 'required');?>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group float-right">
                            <label class="control-label submit"></label>
                            <input type="submit" name="submit" id="submit" 
                                class="button button-primary" value="Save">                
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <strong>Fields and mappings</strong>
                        <button id="add-mapping" class="button-primary mt-md-18px float-right">Add Mapping</button>
                    </div>
                    <div class="col-md-12">                        
                        <hr>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <?php $this->print_fields();?>
                    </div>
                </div>
            </div>
        </div>
        <?php    
    } 
    private function print_fields()
    {
    ?>
      <div class="col-md-12 wp-custom-zapier-field-mappings-form hidden">
          <div class="form-group col-md-3">
            <label class="control-label">Api Field Name</label>
            <input type="text" class="form-control" name="api_field" id="wp_zapier_form_api_field">
          </div>
          <div class="form-group col-md-3">
            <label class="control-label">Wordpress Field Name</label>
            <input type="text" value="" class="form-control" name="wp_field" id="wp_zapier_form_wp_field">
          </div>
          <div class="form-group col-md-3">
            <label class="control-label">Wordpress Field Type</label>
            <select class="form-control" name="wp_field_type" id="wp_zapier_form_field_type">
                <option value="post">Post</option>
                <option value="meta">Post Meta/Custom Field</option>
                <option value="schedule">Schedule</option>
                <option value="taxonomy">Taxonomy</option>
            </select>
          </div>
          <div class="form-group col-md-3 text-right">
              <button id="save-mapping" class="button-primary mt-md-18px">Save Mapping</button>
          </div>
      </div>
      <div class="table-header float-right search-bar">
          <input type="search" name="search" id="search" placeholder="search">
      </div>
      <table class="wp-list-table widefat striped" id="wp-custom-zapier-field-mappings">
          <thead>
              <tr><th>Api Field</th><th>Wordpress Field</th><th>Field Type</th><th colspan="2">Actions</th></tr>
          </thead>
          <tbody></tbody>
          <tfoot></tfoot>
      </table>      
    <?php
    }
    private function print_input($title, $method, $style='', $required = '')
    { 
        $title = esc_html($title);
        $style = esc_html($style);
        $required = esc_html($required);
        printf('
                <div class="form-group" %s id="%s">
                    <label class="control-label %s">%s</label>',
                    $style, str_replace(" ", "-", strtolower($title))."-wrapper", $required, $title);
                    $this->{$method}();
        printf('</div>');
    }

    private function api_security_key_callback()
    {
        $name = esc_html(CUSTOM_WP_ZAPIER_SETTINGS_GROUP);
        $value = esc_html($this->get_settings('security_key'));
        printf( 
            '<input id="security_key" class="regular-text" name="%s[security_key]" value="%s" />',
            $name,$value            
        );
    }


    /**
     * Register CSS and JS for page
     *
     * @uses "admin_enqueue_scripts" action
     */
    public function register_assets()
    {
        $ver = CUSTOM_WP_ZAPIER_PLUGIN_VERSION;
        wp_register_script( 
                $this->slug."-notificationService", 
                $this->assets_url . 'js/notificationService.js', array( 'jquery' ) , $ver);
        wp_register_script( 
                $this->slug."-dataService", 
                $this->assets_url . 'js/dataService.js', array( 'jquery' ) , $ver);
        wp_register_script( 
                $this->slug."-admin", 
                $this->assets_url . 'js/admin.js', array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-droppable' ), $ver );
        
        wp_register_style( $this->slug, $this->assets_url . 'css/admin.css', array(), $ver);
        
        $toLocalize = array(
            'strings' => array(
                'saved' => __( 'Settings Saved', 'text-domain' ),
                'error' => __( 'Error', 'text-domain' )
            ),
            'api'     => array(
                'base'   => esc_url_raw( rest_url( 'custom-wp-zapier/v1' ) ),
                'nonce' => wp_create_nonce( 'wp_rest' ),                
            ),
            'container' => ".wp-custom-zapier-settings-wrap"
        );
       
        wp_localize_script( $this->slug."-notificationService", 'CustomWpZapier', $toLocalize );
        wp_localize_script( $this->slug."-dataService", 'CustomWpZapier', $toLocalize );
        wp_localize_script( $this->slug."-admin", 'CustomWpZapier', $toLocalize ); 
        $this->enqueue_assets(); 
    }

   /**
     * Enqueue CSS and JS for page
    */
    public function enqueue_assets(){  
        wp_enqueue_script( $this->slug."-notificationService" );
        wp_enqueue_script( $this->slug."-dataService" );
        wp_enqueue_script( $this->slug."-admin" );
        wp_enqueue_style( $this->slug );
    }

    private function get_settings($setting_name){
        $settings = (array)get_option( CUSTOM_WP_ZAPIER_SETTINGS_GROUP );
        return !empty($settings[$setting_name]) ?  $settings[$setting_name] : "";
    }
} 

if( is_admin() )
{
    new Settings(CUSTOM_WP_ZAPIER_PLUGIN_DIR);
}
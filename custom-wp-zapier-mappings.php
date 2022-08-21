<?php
namespace CustomWpZapier\Mappings;
class Mappings
{
    static function api_post_fields()
    {
        return[
            'Ad_Title__c' => 'post-title',
            'security_key' => 'security-key',
            'Request_Status__c' => 'post_status',
            'Account_Wordpress_User__c' => 'post_author'
        ];
    }
    static function api_meta_fields()
    { 
        return array(        
            'Ad_Details__c' => '_job_description', //Description        
            'Name' => '_request-name', //Request Name
            'Wordpress_Cover_Image_URL__c' => '_job_cover', //Cover Image
            'Wordpress_Banner_URL_from_Account__c' => '_job_gallery', //Banner Image []     
            'Retailer_Phone__c' => '_job_phone', //Phone Number
            'Website__c' => '_website', //Website url       
            'Menu__c' => '_menu', //Menu url,
            'Account_Retailer_Deals__c' => '_dispensary-deal-page', //Dispensary Deal Page url
            'Location__c' => '_job_location',       
            'Wordpress_Account_Listing_Id__c' => '_wordpress-account-listing-id',//Wordpress Account Listing ID
            'Request_ID_18_Digit' => '_salesforce-deal-id', //Salesforce Request ID Unique
            'Account_ID_18_Digit' => '_salesforce-account-id', //Salesforce Account ID
            'Account_ID_Number__c' => '_salesforce-account-number', //Salesforce Account Number
            'Deal_Terms__c' => '_terms-conditions',
            'Promotion_Expiration_Date__c' => '_job_expires',
            'Priority__c' => '_featured',
            'Verification_Status__c' => '_claimed',
            'Listing_Type__c' => '_case27_listing_type'
        );
    }

    static function api_schedule_fields()
    { 
        return array(
            'Start_Date_Time__c' => 'start_date',
            'End_Date_Time__c' => 'end_date',
            'Frequency__c' => 'frequency',
            'Repeat_Unit__c' => 'repeat_unit',
            'Repeat_Until' => 'repeat_end',   
        );
    }

    static function api_taxonomy_fields()
    {
        return  array(                 
            'Retailer_Type__c' => 'retailer-type', //Retailer Type []
            'Badges__c' => 'honeypottt-premium', //Badges []
            'Amenities__c' => 'case27_job_listing_tags', //Amenities []
            'Product_Category_for_Upload__c' => 'job_listing_category', //Product Type[]
            'Secondary_Product_Categories__c' => 'product-subcategory', //Additional Product Types[]
            'Brands__c' => 'brand-s', //Brands[]
            'Deal_Day_Rollup_Field__c' => 'deal-day-s', //Deal Days[]
            'Region__c' => 'region', //Region[]
            'Pricing__c' => 'pricing', //Pricing[],
            'Deal_Type__c' => 'deal-type', //Deal Type[]
        );
    }

    static function api_workhour_fields()
    {
        return  array(
            'Monday_Open__c' => 'monday-opening-hours',
            'Monday_Close__c' => 'monday-closing-hours',
            'Tuesday_Open__c' => 'tuesday-opening-hours',
            'Tuesday_Close__c' => 'tuesday-closing-hours',
            'Wednesday_Open__c' => 'wednesday-opening-hours',
            'Wednesday_Close__c' => 'wednesday-closing-hours',
            'Thursday_Open__c' => 'thursday-opening-hours',
            'Thursday_Close__c' => 'thursday-closing-hours',
            'Friday_Open__c' => 'friday-opening-hours',
            'Friday_Close__c' => 'friday-closing-hours',
            'Saturday_Open__c' => 'saturday-opening-hours',
            'Saturday_Close__c' => 'saturday-closing-hours',
            'Sunday_Open__c' => 'sunday-opening-hours',
            'Sunday_Close__c' => 'sunday-closing-hours',
            'Timezone__c' => 'timezone'
        );
    }
    static function api_related_listings_fields(){
        return [
            'Account_Name__c' => 'retailer-deals', //Retailer []  Relate post
        ];
    }
    static function post_statuses()
    {
        return [
            'publish', 
            'future,', 
            'draft', 
            'pending', 
            'private', 
            'trash', 
            'auto-draft', 
            'inherit'
        ];
    }
}
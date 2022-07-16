<?php
namespace CustomWpZapier\Utils;
if ( ! function_exists( 'sanitize_text_field' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/formatting.php' );
}
class Utils
{
    /**
    * Sanitizes the requested fields in the current $_POST array and returns the result.
    *
    * Usage example:
    * {fields:array ['a']} # [a => sanitized(value)]
    */
    static function sanitize_post_values($fields)
    {
        $sanitized_post_values = [];
        foreach ($fields as $key => $value) 
        {
            if(isset($_POST[$key]))
            {
                $sanitized_post_values[$key] = sanitize_text_field($_POST[$key]);
            }
        }
        return $sanitized_post_values;
    }

    /**
    * Geocodes an address.
    *
    * Usage example:
    * {code_address "NY US"} # [lat,lng]
    */
    static function code_address($api_key, $address)
    {
        // url encode the address
        $address = urlencode($address);
          
        // google map geocode api url
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key=$api_key";
      
        // get the json response
        $resp_json = file_get_contents($url);
          
        // decode the json
        $resp = json_decode($resp_json, true);
      
        // response status will be 'OK', if able to geocode given address 
        if($resp['status']=='OK')
        {
      
            // get the important data
            $lat = isset($resp['results'][0]['geometry']['location']['lat']) ? $resp['results'][0]['geometry']['location']['lat'] : "";
            $lng = isset($resp['results'][0]['geometry']['location']['lng']) ? $resp['results'][0]['geometry']['location']['lng'] : "";
            return ['lat' => $lat, 'lng' => $lng];
        }

        return [];
    }
    /**
    * Singularize a string.
    * Converts a word to english singular form.
    *
    * Usage example:
    * {singularize "people"} # person
    */

    static function singularize ($params)
    {
        if (is_string($params))
        {
            $word = str_replace("(s)", "", $params);
        } else if (!$word = str_replace("(s)", "", $params['word'])) {
            return false;
        }

        $singular = array (
            '/(quiz)zes$/i' => '\\1',
            '/(matr)ices$/i' => '\\1ix',
            '/(vert|ind)ices$/i' => '\\1ex',
            '/^(ox)en/i' => '\\1',
            '/(alias|status)es$/i' => '\\1',
            '/([octop|vir])i$/i' => '\\1us',
            '/(cris|ax|test)es$/i' => '\\1is',
            '/(shoe)s$/i' => '\\1',
            '/(o)es$/i' => '\\1',
            '/(bus)es$/i' => '\\1',
            '/([m|l])ice$/i' => '\\1ouse',
            '/(x|ch|ss|sh)es$/i' => '\\1',
            '/(m)ovies$/i' => '\\1ovie',
            '/(s)eries$/i' => '\\1eries',
            '/([^aeiouy]|qu)ies$/i' => '\\1y',
            '/([lr])ves$/i' => '\\1f',
            '/(tive)s$/i' => '\\1',
            '/(hive)s$/i' => '\\1',
            '/([^f])ves$/i' => '\\1fe',
            '/(^analy)ses$/i' => '\\1sis',
            '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\\1\\2sis',
            '/([ti])a$/i' => '\\1um',
            '/(n)ews$/i' => '\\1ews',
            '/s$/i' => ''
        );

        $irregular = array(
            'person' => 'people',
            'man' => 'men',
            'child' => 'children',
            'sex' => 'sexes',
            'move' => 'moves'
        );  

        $ignore = array(
            'equipment',
            'information',
            'rice',
            'money',
            'species',
            'series',
            'fish',
            'sheep',
            'press',
            'sms',
        );

        $lower_word = strtolower($word);
        foreach ($ignore as $ignore_word)
        {
            if (substr($lower_word, (-1 * strlen($ignore_word))) == $ignore_word)
            {
                return $word;
            }
        }

        foreach ($irregular as $singular_word => $plural_word)
        {
            if (preg_match('/('.$plural_word.')$/i', $word, $arr))
            {
                return preg_replace('/('.$plural_word.')$/i', substr($arr[0],0,1).substr($singular_word,1), $word);
            }
        }

        foreach ($singular as $rule => $replacement)
        {
            if (preg_match($rule, $word))
            {
                return preg_replace($rule, $replacement, $word);
            }
        }

        return $word;
    }

    /**
    * returns date in Y-m-d H:i:s format.
    * takes php utc date string e.g., 2022-08-01T00:00:00Z
    *
    * Usage example:
    * {utc_date_to_my_sql "2022-08-01T00:00:00Z"} # returns 2022-08-01 00:00:00
    */
    static function utc_date_to_my_sql($date_str){
        try
        {
            $dt_obj = new \DateTime($date_str);
            return date_format($dt_obj, 'Y-m-d H:i:s');
        }
        catch(Exception $ex)
        {
            return $date_str;
        }
    }

    /**
    * returns time in 24 hour form
    * takes php utc date string e.g., 21:00
    *
    * Usage example:
    * {am_pm_to_24 "09:00 PM"} # returns 21:00
    */
    static function am_pm_to_24($hour_str){
        return date("H:i", strtotime($hour_str));
    }

    /**
    * returns url string
    * takes anchor tag
    *
    * Usage example:
    * {<a href="example.com">click here</a>} # returns example.com
    */
    static function get_url($link){ 
        
        if(strpos($link, "href") === FALSE)
            return $link;
        
        preg_match_all('/<a[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', stripslashes($link), $result);

        if (!empty($result) && !empty($result['href'])) 
        {
            return $result['href'][0];
        }

        return $link;
    }

    /**
    * returns TRUE if valid timezone was passed
    * takes $timezone string
    *
    * Usage example:
    * {America/Los_Angeles} # returns TRUE
    * {something wrong} # returns FAKSE
    */
    static function is_valid_time_zone($timezone)
    {
        if (in_array($timezone, \DateTimeZone::listIdentifiers())) {
            return TRUE;
        }
        else 
        {
            return FALSE;
        }
    }

    /**
    * returns time TRUE if valid time was passed
    * takes $time string
    *
    * Usage example:
    * {09:00 AM} # returns TRUE
    * {something wrong} # returns FALSE
    */
    static function is_valid_time($time, $format = 'h:i A')
    {
        $d = \DateTime::createFromFormat($format, $time);
        return $d ? TRUE : FALSE;
    }

    /**
    * returns time TRUE if valid date was passed
    * takes $date string
    *
    * Usage example:
    * {2022-08-01T00:00:00Z} # returns TRUE
    * {something wrong} # returns FALSE
    */
    static function is_valid_date($date, $format = "Y-m-dH:i:s")
    {  
        if($format == "Y-m-dH:i:s")
        {
            $date = preg_replace(["/T/", "/.[0-9]{3}Z$/", "/Z/"], "", $date);
        }
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    /**
    * returns time TRUE if valid unit was passed
    * takes $dateUnit string
    *
    * Usage example:
    * {DAY} # returns TRUE
    * {something wrong} # returns FALSE
    */
    static function is_valid_date_unit($unit)
    { 
        return in_array(strtoupper($unit), ['DAY', 'WEEK', 'MONTH', 'YEAR']);
    }
}
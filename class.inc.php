<?php 

class GeoNames {
    private $locations;
    
    
    
    public function prepareGeoData($link) {
        
        function StringToArray(&$value, $keys, $gn_keys) { 
                global $geonames_keys;

                $value = explode(chr(9), $value);
                $value = array_combine($gn_keys, $value);
            return $value;
        }
        
        function cleanPhoneCode(&$phonecode) {
            $phonecode = preg_replace('/[+-]/','', $phonecode);
            return $phonecode;
        }

        //main part

        $db = file_get_contents($link); // get data from geonames
        $DNC = preg_split('/\r\n|\r|\n/',$db);  // split string into array
        $geonames_keys = array_values(preg_grep('/^#ISO.*/', $DNC));    // find string with keys and resort array
        $geonames_keys = explode(chr(9), $geonames_keys[0]);            // split string to array of keys
        
        $DNC = preg_replace('/^#.*/', '', $DNC);                        //  make elements with comments rows empty  
        $DNC = array_diff($DNC, array(''));                             // remove elements with empty values
        $DNC = array_values($DNC);                                      // reindexate array keys
        array_walk($DNC, 'cleanPhoneCode');                              //remove any + - chars from phone code
        array_walk($DNC, 'StringToArray', $geonames_keys);              // convert each string element to accociated array with keys from geonames

        $this->locations = $DNC;
    }
    
    public function getDialedNumContinent($phone, $continents){
        $cc  = substr($phone, 0, 1); // get continent code from phone number
        $ret ='';
        foreach ($continents as $continent) {  // get one array of array of arrays
            foreach ($continent as $cnt) {   // get each element from current array
                if ( strpos($cnt['Phone'], $cc ) == 0 ) {  // check if first digit from phone number equal to first digit of continent code 
                    $ret = $cnt['Continent']; 
                }
            }
        }            
        return $ret;
    }

}

?>
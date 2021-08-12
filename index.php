<?php 
if (isset($_GET['pw']) && $_GET['pw'] === 'mytower') {
    if (!isset($_GET['action'])) { $_GET['action'] =''; }
    switch ($_GET['action']) {
        
        default:
        $formtpl = file_get_contents('form.tpl.html');
        $formtpl = str_replace('{ACTION}', '?action=upload&pw=mytower', $formtpl);
        echo $formtpl;
        break;

        case 'upload':
            echo "<pre>";
            //require_once('common.inc.php');
            require_once('class.inc.php');

            $IPSKEY = 'fa6519241cafa3399764751697f32c9a';
            $DT = new DateTime();
            $Continents = new GeoNames(); // Dialed Number Continent
            $Continents->prepareGeoData('http://download.geonames.org/export/dump/countryInfo.txt');  // Dialed Number Location

            $Continent_names = array (
                'NA' => 'North America',
                'AF' => 'Africa',
                'EU' => 'Europe',
                'SA' => 'South America',
                'AS' => 'Asia',
            );
  
            $newfilename = date('Y-m-d_H-i-s',$DT->getTimestamp()).'_'.$_FILES['uplodadedfile']['name'];
            if ($_FILES['uplodadedfile']['type'] =='application/vnd.ms-excel') {
                if (move_uploaded_file($_FILES['uplodadedfile']['tmp_name'], 'files/'.$newfilename)) {
                    //echo 'file "'.$_FILES['uplodadedfile']['name'].'" is uploaded like a "'.$newfilename.'"<hr>';

                    $csvfh = fopen('files/'.$newfilename, 'r');

                    // prepare csv data. add customer continent filed and Dialed Number Continent field
                    while (($csvrow = fgetcsv($csvfh)) !== false) {

                        // get the consumer's continent name by customer's ip
                        $url = 'http://api.ipstack.com/'.$csvrow[4].'?access_key='.$IPSKEY.'&fields=continent_code';
                        $CustomerContinent = json_decode(file_get_contents( $url , false));   

               
                        $customers[] = array ( 
                            'CustomerID'        => $csvrow[0],
                            'CallDate'          => $csvrow[1],
                            'CallDuration'      => $csvrow[2],
                            'DialedNumber'      => $csvrow[3],
                            'CustomerIP'        => $csvrow[4],
                            'CustomerContinent' => $CustomerContinent->continent_code,
                            'DialedNumContinent'=> $Continents->getDialedNumContinent($csvrow[3], $Continents)
                        ); 
                    }
                    sort($customers);
                    $TotalDuration = 0;
                    $CallCounter_SC = 0; // same continent call counter
                    $DurationCounter_SC = array ( 'NA' =>0, 'AF' => 0, 'EU' => 0, 'SA' => 0, 'AS' => 0); // same continent call duration counter

                    // calc total calls duration
                    foreach ($customers as $customer) {
                        if ($customer['CustomerContinent'] == $customer['DialedNumContinent']) {
                            $CallCounter_SC++;
                            
                            $DurationCounter_SC[$customer['CustomerContinent']] = $DurationCounter_SC[$customer['CustomerContinent']]+$customer['CallDuration'];
                        }
                        $TotalDuration  = $TotalDuration + $customer['CallDuration'];
                        
                    }


                    echo 'Total numbers of all calls: '.count($customers).'<br>';
                    echo 'Total duration of all calls: '.
                        date("H", mktime(0, 0, $TotalDuration)).' hrs '.
                        date("i", mktime(0, 0, $TotalDuration)).' min. '.
                        date("s", mktime(0, 0, $TotalDuration)).'sec. ('.$TotalDuration.' sec.)<br>';
                    
                    echo 'Number of calls within the same continent: '.$CallCounter_SC.'<br>';
                    echo 'Total Duration of calls within the same continent: <br> ';
                    
                    foreach ($DurationCounter_SC as $cont) {
                        if (!empty($cont)) {
                            echo $Continent_names[key($DurationCounter_SC)]." - ".$cont.'<br>';
                        }
                    }
 
                    fclose($csvfh);
                    
                }
            }
        break;
    }
        
} // if $_GET['pw']
?>
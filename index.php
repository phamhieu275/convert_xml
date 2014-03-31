<?php
/*
 * convert xml to array
 * @param {string} $xml
 * @return array
 */
function xml2assoc($xml){ 
    $assoc = NULL; 
    $n = 0; 
    while($xml->read()){ 
        if($xml->nodeType == XMLReader::END_ELEMENT){
            break; 
        }
        if($xml->nodeType == XMLReader::ELEMENT and !$xml->isEmptyElement){ 
            $assoc[$n]['tag'] = $xml->name; 
            if($xml->hasAttributes){
                while($xml->moveToNextAttribute()){
                    $assoc[$n]['atr'][$xml->name] = $xml->value;
                }
            } 
            $assoc[$n]['val'] = xml2assoc($xml); 
            $n++; 
        } 
        else if($xml->isEmptyElement){ 
            $assoc[$n]['tag'] = $xml->name; 
            if($xml->hasAttributes){
                while($xml->moveToNextAttribute()){
                    $assoc[$n]['atr'][$xml->name] = $xml->value;
                }
            }
            $assoc[$n]['val'] = ""; 
            $n++;                
        } 
        else if($xml->nodeType == XMLReader::TEXT){
            $assoc = $xml->value;
        }
    } 
    return $assoc; 
}

/*
 * parse array data to standard data
 * @param {array} $data
 * @return array
 */
function parseData($data){
    $result = array();
    try{
        $i = $n = 0;
        while($i < count($data)){
            $key = $n;

            //for tag name = dict
            if($data[$i]['tag'] === 'dict'){
                $result[$key] = parseData($data[$i]['val']);
                $n++;
                $i++;
                continue;
            }

            //set key
            if($data[$i]['tag'] === 'key'){
                $key = $data[$i]['val'];
            }

            //check next tag is exist
            if(!isset($data[$i + 1])){
                throw new Exception('Don\'t find next node.');
            }
            
               
            if($key === 'moveable'){
                //set boolean
                $val = $data[$i + 1]['tag'] === "true" ? true : false;
            }else{
                switch($data[$i + 1]['tag']){
                    case 'array':
                        //for tag is array: call recursive function 
                        $val = parseData($data[$i + 1]['val']);
                        break;
                    case 'integer':
                        //format integer
                        $val = intval($data[$i + 1]['val']);
                        break;
                    default:
                        $val = $data[$i + 1]['val'];
                }
            }
            
            $result[$key] = $val;
            $n++;

            //plus 2 into index
            $i += 2;
    	}
    }catch(Exeption $e){
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

	return $result;
}


function convert($url){
    $content = file_get_contents($url);

    $xml = new XMLReader();
    $xml->XML($content);

    $rawData = xml2assoc($xml);

    //remove header data, get cells data
    $rawData = $rawData[0]['val'][0]['val'];
    // print_r($rawData);

    // echo '<p />';

    $convertedData = parseData($rawData);

    // print_r($convertedData);

    // echo '<p />';

    return json_encode($convertedData);
}


/*
 * read xml file, call convert function
 * create json file
 * @param {string} $source The source path (filename ! folder)
 * @param {string} $dest The destination path
 * @return void
 */
function run($source, $dest){
    if(is_dir($source)){
        $handle     = opendir($source);
        if (!$handle) {
            echo "Don't open folder.";
            return false;
        }

        while (false !== ($entry = readdir($handle))) {
            if ($entry == "." || $entry == "..") {
                continue;
            }
            $url = $source . '/' . $entry;

            //call convert
            $jsonData = convert($url);

            $partInfo = pathinfo($source . '/' . $entry);
            $filename = $partInfo['filename'] . '.json';

            echo 'filename: '.$filename;
            echo '<br/>';
            echo $jsonData;
            echo '<p />';

            file_put_contents($dest . '/ '. $filename, $jsonData, LOCK_EX);
        }
    }else{
        convert($source);
    }
}

run('source/levels_special', 'dest/levels_special_json');

?>
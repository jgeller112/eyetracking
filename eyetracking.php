<?php
  header("Access-Control-Allow-Methods: GET, PUT, OPTIONS");
  header("Access-Control-Allow-Headers: authorization, x-requested-with, content-type, access-control-allow-methods");
  function subarray(){
        $return = [];
        if (func_num_args()>0){
            $ar = func_get_arg(0);
            if (gettype($ar) == "array" && func_num_args()>1){
                for ($i = 1; $i < func_num_args(); $i++){
                    $key = func_get_arg($i);
                    if (array_key_exists($key, $ar)){
                        $return[$i] = $ar[$key];
                    }
                }
            }
        }
        return $return;
  }
  function lzw_decode($s) {
    mb_internal_encoding('UTF-8');
    $dict = array();
    $currChar = mb_substr($s, 0, 1);
    $oldPhrase = $currChar;
    $out = array($currChar);
    $code = 256;
    $phrase = '';
    for ($i=1; $i < mb_strlen($s); $i++) {
        $currCode = implode(unpack('N*', str_pad(iconv('UTF-8', 'UTF-16BE', mb_substr($s, $i, 1)), 4, "\x00", STR_PAD_LEFT)));
        if($currCode < 256) {
            $phrase = mb_substr($s, $i, 1);
        } else {
           $phrase = isset($dict[$currCode]) ? $dict[$currCode] : ($oldPhrase.$currChar);
        }
        $out[] = $phrase;
        $currChar = mb_substr($phrase, 0, 1);
        $dict[$code] = $oldPhrase.$currChar;
        $code++;
        $oldPhrase = $phrase;
    }
    // var_dump($dict);
    return(implode($out));
  }
  function generateTable($filename){
    $dictionary = array();
    $file = fopen($filename,"r");
    while(!feof($file)){
        $line = rtrim(fgets($file), "\n\r");
        $cells = explode(",",$line);
        $trial = $cells[0];
        $param = $cells[1];
        $value = $cells[2];
        if (!array_key_exists($param,$dictionary)){
            $dictionary[$param] = array();
        }
        $dictionary[$param][$trial] = explode('.', lzw_decode( $value ) );
    }
    
    
    $parameters = array_keys($dictionary);
    $stringTable = 'trial';
    for ($p = 0; $p < count($parameters); $p++){
        $stringTable = $stringTable.','.$parameters[$p];
    }
    $trials = array_keys($dictionary['times']);
    for ($t = 0; $t < count($trials); $t++){
        $trial = $trials[$t];
        $timelines = $dictionary['times'][$trial];
        $time = 0;
        for ($l = 0; $l < count($timelines); $l++){
          $stringTable = $stringTable."\n".$trial;
          for ($p = 0; $p < count($parameters); $p++){
            $param = $parameters[$p];
            $value = $dictionary[$param][$trial][$l];
            if ($param=='times'){
                $time = $time + intval($value);
                $value = $time;
            }
            $stringTable = $stringTable.','.$value;
          }
        }
    }
    return($stringTable);
  }
  if ($_POST['json']) {
        $json = json_decode($_POST['json'], true);
        $directory = mb_ereg_replace("([^\w\d])", '', $json['experiment']);
        $directory = mb_ereg_replace("([\.]{2,})", '', $directory);
        if (is_dir($directory) === false) {
            mkdir($directory);
        }
        $data = fopen($directory.'/'.$json['id'], "a") or die("Unable to open file");
        flock($data, LOCK_EX);
        $line = subarray($json, 'pcnumber', 'parameter', 'value');
        fwrite($data, implode(',',$line));
        fwrite($data, "\n");
        flock($data, LOCK_UN);
        fclose($data);
  }
  else if (isset($_GET['experiment'])) {
      header("Content-Type: text/plain");
      print( generateTable($_GET['experiment']) );
  }
  else{
?>
<html>
<head>
    <title>Get EyeTracker data</title>
</head>
<body>
    <div>
        <form method='get' action="">
            Experiment's URL: <input type="text" name="experiment"><br>
            <input type="submit" value="Submit">
        </form>
    </div>
</body>
</html>
<?php
}
?>
<?

$options = "";
if (($handle = fopen("gemeenten.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
    	$q = str_replace("http://www.wikidata.org/entity/", "", $data[0]);
    	if($qgemeente==$q){
    		$options .= "<option selected=\"s\" value=\"" . $q . "\">" . $data[1] . "</option>\n";
    		$gemeentenaam = $data[1];
    	}else{
    		$options .= "<option value=\"" . $q . "\">" . $data[1] . "</option>\n";
    	}
    }
    fclose($handle);
}
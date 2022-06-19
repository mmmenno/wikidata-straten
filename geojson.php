<?php

ini_set('memory_limit', '1024M');

$sparqlQueryString = "
SELECT ?straat ?straatLabel ?naamgeverLabel ?naamgeverDescription ?aanlegjaar ?coords WHERE {
    ?straat wdt:P31 wd:Q79007 .
  	?straat wdt:P131 wd:" . $qgemeente . " .
  	OPTIONAL{
    	?straat wdt:P571 ?aanlegdatum .
    	BIND(year(?aanlegdatum) AS?aanlegjaar)
  	}
	OPTIONAL{
		?straat wdt:P138 ?naamgever .
	}
  	?straat wdt:P625 ?coords .
    SERVICE wikibase:label { bd:serviceParam wikibase:language \"nl\". }
}
LIMIT 10000
";

$endpointUrl = 'https://query.wikidata.org/sparql';
$url = $endpointUrl . '?query=' . urlencode($sparqlQueryString) . "&format=json";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch,CURLOPT_USERAGENT,'MonumentMap');
$headers = [
    'Accept: application/sparql-results+json'
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec ($ch);
curl_close ($ch);

$data = json_decode($response, true);


//print_r($data);

$fc = array("type"=>"FeatureCollection", "features"=>array());

$beenthere = array();

foreach ($data['results']['bindings'] as $k => $v) {

	// we don't want multiple features of one wikidata item, just because it has multiple 'types'
	if(in_array($v['straat']['value'],$beenthere)){
		continue;
	}
	$beenthere[] = $v['straat']['value'];

	if(!isset($v['naamgeverLabel']['value'])){
		$v['naamgeverLabel']['value'] = null;
	}

	if(!isset($v['naamgeverDescription']['value'])){
		$v['naamgeverDescription']['value'] = null;
	}

	if(!isset($v['aanlegjaar']['value'])){
		$v['aanlegjaar']['value'] = null;
	}

	$straat = array("type"=>"Feature");
	$props = array(
		"wdid" => $v['straat']['value'],
		"label" => $v['straatLabel']['value'],
		"aanlegjaar" => $v['aanlegjaar']['value'],
		"nlabel" => $v['naamgeverLabel']['value'],
		"ndesc" => $v['naamgeverDescription']['value']
	);
	
	
	$coords = str_replace(array("Point(",")"), "", $v['coords']['value']);
	$latlon = explode(" ", $coords);
	$straat['geometry'] = array("type"=>"Point","coordinates"=>array((double)$latlon[0],(double)$latlon[1]));
	
	$straat['properties'] = $props;
	$fc['features'][] = $straat;

}

$json = json_encode($fc);

file_put_contents("geojson/" . $qgemeente . '.geojson', $json);










function wkt2geojson($wkt){
	$coordsstart = strpos($wkt,"(");
	$type = trim(substr($wkt,0,$coordsstart));
	$coordstring = substr($wkt, $coordsstart);

	switch ($type) {
	    case "LINESTRING":
	    	$geom = array("type"=>"LineString","coordinates"=>array());
			$coordstring = str_replace(array("(",")"), "", $coordstring);
	    	$pairs = explode(",", $coordstring);
	    	foreach ($pairs as $k => $v) {
	    		$coords = explode(" ", trim($v));
	    		$geom['coordinates'][] = array((double)$coords[0],(double)$coords[1]);
	    	}
	    	return $geom;
	    	break;
	    case "POLYGON":
	    	$geom = array("type"=>"Polygon","coordinates"=>array());
			preg_match_all("/\([0-9. ,]+\)/",$coordstring,$matches);
	    	//print_r($matches);
	    	foreach ($matches[0] as $linestring) {
	    		$linestring = str_replace(array("(",")"), "", $linestring);
		    	$pairs = explode(",", $linestring);
		    	$line = array();
		    	foreach ($pairs as $k => $v) {
		    		$coords = explode(" ", trim($v));
		    		$line[] = array((double)$coords[0],(double)$coords[1]);
		    	}
		    	$geom['coordinates'][] = $line;
	    	}
	    	return $geom;
	    	break;
	    case "MULTILINESTRING":
	    	$geom = array("type"=>"MultiLineString","coordinates"=>array());
	    	preg_match_all("/\([0-9. ,]+\)/",$coordstring,$matches);
	    	//print_r($matches);
	    	foreach ($matches[0] as $linestring) {
	    		$linestring = str_replace(array("(",")"), "", $linestring);
		    	$pairs = explode(",", $linestring);
		    	$line = array();
		    	foreach ($pairs as $k => $v) {
		    		$coords = explode(" ", trim($v));
		    		$line[] = array((double)$coords[0],(double)$coords[1]);
		    	}
		    	$geom['coordinates'][] = $line;
	    	}
	    	return $geom;
	    	break;
	    case "POINT":
			$coordstring = str_replace(array("(",")"), "", $coordstring);
	    	$coords = explode(" ", $coordstring);
	    	//print_r($coords);
	    	$geom = array("type"=>"Point","coordinates"=>array((double)$coords[0],(double)$coords[1]));
	    	return $geom;
	        break;
	}
}








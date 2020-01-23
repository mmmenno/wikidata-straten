<?php

if(isset($_GET['gemeente'])){
  $qgemeente = $_GET['gemeente'];
}else{
  $qgemeente = "Q9920";
}

if(!file_exists(__DIR__ . "/geojson/" . $qgemeente . ".geojson") || isset($_GET['uncache'])){
  include("geojson.php");
}


include("options.php");


?><!DOCTYPE html>
<html>
<head>
  
<title>Straten in Haarlem</title>

  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <script
  src="https://code.jquery.com/jquery-3.2.1.min.js"
  integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
  crossorigin="anonymous"></script>

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.1.0/dist/leaflet.css" integrity="sha512-wcw6ts8Anuw10Mzh9Ytw4pylW8+NAD4ch3lqm9lzAsTxg0GFeJgoAtxuCLREZSC5lUXdVyo/7yfsqFjQ4S+aKw==" crossorigin=""/>
  <script src="https://unpkg.com/leaflet@1.1.0/dist/leaflet.js" integrity="sha512-mNqn2Wg7tSToJhvHcqfzLMU6J4mkOImSPTxVZAdo+lcPlk+GhZmYgACEe0x35K7YzW1zJ7XyJV/TT1MrdXvMcA==" crossorigin=""></script>
  <link rel="stylesheet" href="styles.css" />

  
</head>
<body>

<div id="bigmap"></div>


<div id="legenda">
  <h1>Haarlemse straten op Wikidata, naar jaar van aanleg</h1>
  <h3>(een project van Louisa en Fenna)</h3>
  <div class="legendapoint" style="background-color: #838385;"></div> aanleg onbekend<br />
  <div class="legendapoint" style="background-color: #4575b4;"></div> aanleg > 2000<br />
  <div class="legendapoint" style="background-color: #74add1;"></div> aanleg > 1980<br />
  <div class="legendapoint" style="background-color: #abd9e9;"></div> aanleg > 1960<br />
  <div class="legendapoint" style="background-color: #ffffbf;"></div> aanleg > 1940<br />
  <div class="legendapoint" style="background-color: #fee090;"></div> aanleg > 1920<br />
  <div class="legendapoint" style="background-color: #fdae61;"></div> aanleg > 1900<br />
  <div class="legendapoint" style="background-color: #f46d43;"></div> aanleg > 1870<br />
  <div class="legendapoint" style="background-color: #a50026;"></div> aanleg < 1870<br />


  <div id="straatlabel"></div>
  <div id="bouwjaar"></div>
  <div id="naamgeverlabel"></div>
  <div id="naamgeverdescription"></div>

  <!-- <form>
    <select name="gemeente">
      <?php echo $options ?>
    </select>
    <button>go</button>
  </form>

  <p>meer info op <a target="_blank" href="https://github.com/mmmenno/rijksstraaten-bag#rijksstraaten--bag">GitHub</a></p> -->
</div>

<script>
  $(document).ready(function() {
    createMap();
    refreshMap();
  });

  function createMap(){
    center = [52.381016, 4.637126];
    zoomlevel = 16;
    
    map = L.map('bigmap', {
          center: center,
          zoom: zoomlevel,
          minZoom: 1,
          maxZoom: 20,
          scrollWheelZoom: true,
          zoomControl: false
      });

    L.control.zoom({
        position: 'bottomright'
    }).addTo(map);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
      subdomains: 'abcd',
      maxZoom: 19
    }).addTo(map);
  }

  function refreshMap(){
    
    $.ajax({
      type: 'GET',
      url: 'wijken.geojson',
      dataType: 'json',
      success: function(jsonData) {

         if (typeof wijken !== 'undefined') {
            map.removeLayer(wijken);
         }

         wijken = L.geoJson(null, {
            style: function(feature) {
               return {
                  color: "#FFF",
                  fillOpacity: 0,
                  weight: 1,
                  clickable: true
               }
            }
         }).addTo(map);

         wijken.addData(jsonData).bringToFront();

      }

   });


    $.ajax({
          type: 'GET',
          url: 'geojson/<?= $qgemeente ?>.geojson',
          dataType: 'json',
          success: function(jsonData) {
            if (typeof streets !== 'undefined') {
              map.removeLayer(streets);
            }

            streets = L.geoJson(null, {
              pointToLayer: function (feature, latlng) {                    
                  return new L.CircleMarker(latlng, {
                      color: "#FC2211",
                      radius:4,
                      weight: 1,
                      opacity: 0.8,
                      fillOpacity: 0.8
                  });
              },
              style: function(feature) {
                return {
                    color: getColor(feature.properties),
                    clickable: true
                };
              },
              onEachFeature: function(feature, layer) {
                layer.on({
                    click: whenClicked
                  });
                }
              }).addTo(map);

              streets.addData(jsonData).bringToFront();
          
              map.fitBounds(streets.getBounds());
              //$('#straatinfo').html('');
          },
          error: function() {
              console.log('Error loading data');
          }
      });
  }

  function getColor(props) {

    
      if(props['aanlegjaar'] == null){
        return '#838385';
      }

      var j = props['aanlegjaar'];
      return j > 2000 ? '#4575b4' :
             j > 1980 ? '#74add1' :
             j > 1960  ? '#abd9e9' :
             j > 1940  ? '#ffffbf' :
             j > 1920  ? '#fee090' :
             j > 1900  ? '#fdae61' :
             j > 1870   ? '#f46d43' :
                       '#a50026';

    
    
    return '#1DA1CB';
  }

function whenClicked(){
   $("#intro").hide();

   var props = $(this)[0].feature.properties;
   console.log(props);
   $("#straatlabel").html('<h2><a target="_blank" href="' + props['wdid'] + '">' + props['label'] + '</a></h2>');

   if(props['aanlegjaar'] != null){
      $("#bouwjaar").html('aangelegd omstreeks <strong>' + props['aanlegjaar'] + '</strong>');
   }else{
      $("#bouwjaar").html('');
   }

   if(props['nlabel'] != null){
      $("#naamgeverlabel").html('<h3>vernoemd naar: ' + props['nlabel'] + '</h3>');
   }else{
      $("#naamgeverlabel").html('');
   }

   if(props['ndesc'] != null){
      $("#naamgeverdescription").html('(' + props['ndesc'] + ')');
   }else{
      $("#naamgeverdescription").html('');
   }
    
    
}

</script>



</body>
</html>

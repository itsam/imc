//lat,lng,zoom,language,menu_filter_area are set outside
var imc_mod_map;
var imc_markers = [];
var mc;
var infoWindow = new google.maps.InfoWindow({
  maxWidth: 350
});

function imc_mod_map_initialize() {
  var  center = new google.maps.LatLng(lat,lng);
  var mapOptions = {
    zoom: parseInt(zoom),
    center: center
  };
  imc_mod_map = new google.maps.Map(document.getElementById('imc-mod-map-canvas'),
      mapOptions);

    // Construct the polygon.
    if(typeof boundaries != 'undefined') {
        for (var i = 0; i < boundaries.length; i++) {
            var bounds = new google.maps.Polygon({
                paths: boundaries,
                strokeColor: '#FF0000',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#FF0000',
                fillOpacity: 0.05
            });
            bounds.setMap(imc_mod_map);
        }
    }

  setMarkers(center, imc_mod_map);

  google.maps.event.addListener(imc_mod_map, 'click', function() {
    infoWindow.close();
    panelFocusReset();
  });  

  js("div[id^='imc-panel-']").mouseenter(function(e){
      markerBounce( this.id.substring(10) );
  });

  js("div[id^='imc-panel-']").mouseleave(function(e){
      markerIdle( this.id.substring(10) );
  });  
}


function setMarkers(center, map) {
    var json = (function () { 
        var json = null;

        jQuery.ajax({ 
            'async': true, 
            'global': false, 
            'url': "index.php?option=com_imc&task=issues.markers&format=json", 
            'dataType': "json", 
            'success': function (data) {
                json = data; 

                //loop between each of the json elements
                for (var i = 0, length = json.data.length; i < length; i++) {
                    var data = json.data[i],
                    latLng = new google.maps.LatLng(data.latitude, data.longitude); 
                    // Create marker and putting it on the map
                    var marker = new google.maps.Marker({
                        position: latLng,
                        icon: data.category_image,
                        map: map,
                        title: data.title,
                        id: data.id
                    });
                    if(data.category_image == '')
                      marker.setIcon('http://maps.google.com/mapfiles/ms/icons/red-dot.png');
                    
                    imc_markers.push(marker);
                    //bounds.extends(marker.position);

                    infoBox(map, marker, data);

                    if(data.moderation == 1){
                      marker.setIcon('http://maps.google.com/mapfiles/ms/icons/blue-dot.png');
                    }
                }
                resetBounds(map, imc_markers);
                if(clusterer){
                  mc = new MarkerClusterer(map, imc_markers, {
                        imagePath: 'https://rawgit.com/googlemaps/js-marker-clusterer/gh-pages/images/m'
                    });
                }
             },
             'error': function (error) {
                alert('Cannot read markers - See console for more information');
                console.log (error);
             }             
        });
        return json;
    })();

}


function infoBox(map, marker, data) {
    
    // Attaching a click event to the current marker
    google.maps.event.addListener(marker, "click", function(e) {
        infoWindow.setContent('<div class="infowindowcontent">'+data.title+'</div>');
        infoWindow.open(map, marker);
        panelFocus(data.id);
    });
    google.maps.event.addListener(infoWindow,'closeclick',function(){
        panelFocusReset();
    });

    // Creating a closure to retain the correct data 
    // Pass the current data in the loop into the closure (marker, data)
    (function(marker, data) {
      // Attaching a click event to the current marker
      google.maps.event.addListener(marker, "click", function(e) {
        if(data.state == 0){
          infoWindow.setContent('<div class="infowindowcontent imc-warning"><i class="icon-info-sign"></i> '+data.title+'</div>');
        } else {
          infoWindow.setContent('<div class="infowindowcontent">'+data.title+'</div>');
        }
        infoWindow.open(map, marker);
      });
    })(marker, data);
}

// Add a marker to the map and push to the array.
function addMarker(location, map) {
  var marker = new google.maps.Marker({
    position: location,
    map: map
  });
  imc_markers.push(marker);
}

// Sets the map on all imc_markers in the array.
function setAllMap(map) {
  for (var i = 0; i < imc_markers.length; i++) {
    imc_markers[i].setMap(map);
  }
}

// Removes the imc_markers from the map, but keeps them in the array.
function clearMarkers() {
  setAllMap(null);
}

// Shows any imc_markers currently in the array.
function showMarkers() {
  setAllMap(imc_mod_map);
}

// Deletes all imc_markers in the array by removing references to them.
function deleteMarkers() {
  clearMarkers();
  imc_markers = [];
}

function markerBounce(id) {
  var index;
  for (var i=0; i<imc_markers.length; i++) {       
    if(imc_markers[i].id == id){
      // imc_mod_map.setCenter( imc_markers[i].getPosition() );
      imc_markers[i].setAnimation(google.maps.Animation.BOUNCE);
      //google.maps.event.trigger(imc_markers[i], 'click');
      break;
    }
  }
}

function markerIdle(id) {
  var index;
  for (var i=0; i<imc_markers.length; i++) {       
    if(imc_markers[i].id == id){
      imc_markers[i].setAnimation(null);
      break;
    }
  }
}

function resetBounds(map, gmarkers) {
  var a = 0;
  var bounds = null;
  bounds = new google.maps.LatLngBounds();
  for (var i=0; i<gmarkers.length; i++) {
    if(gmarkers[i].getVisible()){
      a++;
      bounds.extend(gmarkers[i].position);  
    }
  }
  if(a > 0){
    map.fitBounds(bounds);
    var listener = google.maps.event.addListener(map, 'idle', function() { 
      if (map.getZoom() > 16) map.setZoom(16); 
      google.maps.event.removeListener(listener); 
    });
  }
}

function panelFocus(id) {
  jQuery('#imc-panel-' + id)[0].scrollIntoView( true );

  //all
  jQuery("[id^=imc-panel-]").removeClass('imc-focus');
  jQuery("[id^=imc-panel-]").addClass('imc-not-focus');

  //selected
  jQuery('#imc-panel-'+id).removeClass('imc-not-focus');
  jQuery('#imc-panel-'+id).addClass('imc-focus');
  
}

function panelFocusReset() {
  jQuery("[id^=imc-panel-]").removeClass('imc-not-focus');
  jQuery("[id^=imc-panel-]").removeClass('imc-focus');
}
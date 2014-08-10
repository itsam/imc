jQuery(document).ready(function() {
	jQuery( "#locateposition" ).click(function() {
	  // Try HTML5 geolocation
	  if(navigator.geolocation) {
	    navigator.geolocation.getCurrentPosition(function(position) {
	      var pos = new google.maps.LatLng(position.coords.latitude,
	                                       position.coords.longitude);


	      map.setCenter(pos);
	    }, function() {
	      handleNoGeolocation(true);
	    });
	  } else {
	    // Browser doesn't support Geolocation
	    handleNoGeolocation(false);
	  }

	});

	jQuery( "#searchaddress" ).click(function() {
		codeAddress();
	});

	jQuery( "#lockaddress" ).click(function() {
		jQuery(this).button('toggle');
		if ( jQuery(this).hasClass('active') ){
			jQuery(this).addClass( "btn-danger" );
		}
		else {
			jQuery(this).removeClass( "btn-danger" );	
		}
	});

	//lock address initially
	jQuery("#lockaddress").click();	
});	

var map;
var marker;
var infowindow;
var geocoder = new google.maps.Geocoder();

function handleNoGeolocation(errorFlag) {
  if (errorFlag) {
    var content = 'Error: The Geolocation service failed.';
  } else {
    var content = 'Error: Your browser doesn\'t support geolocation.';
  }

  infowindow.setContent(content);
  infowindow.open(map, marker);
}

			
function codeAddress() {
	var address = document.getElementById('jform_address').value + ' ".$this->searchterm."';
	geocoder.geocode( { 'address': address, 'language': '".$this->language."'}, function(results, status) {
	  if (status == google.maps.GeocoderStatus.OK) {
		map.setCenter(results[0].geometry.location);
		marker.setPosition(results[0].geometry.location);
		
		if(true){	//check linker checkbox here
			document.getElementById('jform_latitude').value = results[0].geometry.location.lat();
			document.getElementById('jform_longitude').value = results[0].geometry.location.lng();					
		}
		
		updateMarkerAddress(results[0].formatted_address);			

	  } else {
		alert(notfound);
	  }
	});		
}
			
function geocodePosition(pos) {
	geocoder.geocode({
		latLng: pos,
		language: language
	}, function(responses) {
		if (responses && responses.length > 0) {
		  updateMarkerAddress(responses[0].formatted_address);
		} else {
		  updateMarkerAddress(notfound);
		}
	});
}

function updateMarkerPosition(latLng) {
	//update fields
	document.getElementById('jform_latitude').value = latLng.lat();
	document.getElementById('jform_longitude').value = latLng.lng();
}

function updateMarkerAddress(str) {
	if ( !(jQuery("#lockaddress").hasClass('active')) ){
		document.getElementById('jform_address').value = str;
	}
}


function initialize() {
	var center = new google.maps.LatLng(Lat, Lng);

	var mapOptions = {
		center: center,
		zoom: zoom
	}
	map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

	marker = new google.maps.Marker({
		position: center,
		animation: google.maps.Animation.DROP,
		draggable: true
	});
	marker.setMap(map);

	infowindow = new google.maps.InfoWindow({
		content: info
	});	

	// Update current position info.
	updateMarkerPosition(center);
	geocodePosition(center);

	// Add dragging event listeners.
	google.maps.event.addListener(marker, 'dragstart', function() {
		infowindow.close();
	});

	google.maps.event.addListener(marker, 'drag', function() {

	});

	google.maps.event.addListener(marker, 'dragend', function() {
		updateMarkerPosition(marker.getPosition());
		infowindow.setContent(info);
		infowindow.open(map, marker);
		geocodePosition(marker.getPosition());
		//blink();
	});

	infowindow.open(map, marker);
}
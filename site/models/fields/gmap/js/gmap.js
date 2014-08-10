var map;
var marker;
var	infowindow = new google.maps.InfoWindow({
		content: ''
	});
var geocoder = new google.maps.Geocoder();

jQuery(document).ready(function() {
	jQuery( "#locateposition" ).click(function() {
	  // Try HTML5 geolocation
	  if(navigator.geolocation) {
	    navigator.geolocation.getCurrentPosition(function(position) {
	      var pos = new google.maps.LatLng(position.coords.latitude,
	                                       position.coords.longitude);


	      updateMarkerPosition(pos);
	      geocodePosition(pos)
	      map.setCenter(pos);
	      marker.setPosition(pos);

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
			infowindow.setContent(info+'<br />'+info_unlock);
		}
		else {
			jQuery(this).removeClass( "btn-danger" );	
			google.maps.event.trigger(marker, 'dragend', null);	//trigger to display current address	
		}
	});

	//lock address initially
	jQuery("#lockaddress").click();	
});	


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
	var address = jQuery('#jform_address').val() + hiddenterm;
	geocoder.geocode( { 'address': address, 'language': language}, function(results, status) {
	  if (status == google.maps.GeocoderStatus.OK) {
		console.log(results);
		map.setCenter(results[0].geometry.location);
		marker.setPosition(results[0].geometry.location);
		
		if(true){	//check linker checkbox here
			jQuery('#jform_latitude').val(results[0].geometry.location.lat());
			jQuery('#jform_longitude').val(results[0].geometry.location.lng());
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
	//update Lat Lng fields
	jQuery('#jform_latitude').val(latLng.lat());
	jQuery('#jform_longitude').val(latLng.lng());
}

function updateMarkerAddress(str) {
	if ( !(jQuery("#lockaddress").hasClass('active')) ){
		jQuery('#jform_address').val(str);
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
		content: info+'<br />'+info_unlock
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
		if ( jQuery("#lockaddress").hasClass('active') ){
			infowindow.setContent(info+'<br />'+info_unlock); //if geolocation failed
		}
		else{
			infowindow.setContent(info); //if geolocation failed	
		}
		infowindow.open(map, marker);
		geocodePosition(marker.getPosition());
	});

	infowindow.open(map, marker);
}
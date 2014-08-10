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
	      reverseGeocodePosition(pos)
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

		if(results.length > 1){
			//make list with results
			var html = '<ul>';
			for (var i = 0; i < results.length; i++) {
				html += '<li>';
				var lat = results[i].geometry.location.lat();
				var lng = results[i].geometry.location.lng();
				var addr = results[i].formatted_address;
				html += '<a style="cursor: pointer;" onclick="applySearchResult('+lat+','+lng+',\''+addr+'\');jQuery(\'#searchModal\').modal(\'hide\');">'+addr+'</a>';
				html += '</li>';
			};
			html += '</ul>';
			jQuery('#searchBody').html(html);
    		jQuery('#searchModal').modal('show');
		}
		else{
			applySearchResult(results[0].geometry.location.lat(), 
							  results[0].geometry.location.lng(), 	
							  results[0].formatted_address);

		}

	  } else {
		jQuery('#searchBody').html('<h2>'+notfound+'</h2>');
		jQuery('#searchModal').modal('show');
	  }
	});		
}

function applySearchResult(lat, lng, addr){
	var pos = new google.maps.LatLng(lat, lng);
	map.setCenter(pos);
	marker.setPosition(pos);
	jQuery('#jform_latitude').val(lat);
	jQuery('#jform_longitude').val(lng);
	updateMarkerAddress(addr);
}
			
function reverseGeocodePosition(pos) {
	geocoder.geocode({
		latLng: pos,
		language: language
	}, function(responses) {
		if (responses && responses.length > 0) {
		  updateMarkerAddress(responses[0].formatted_address);
		} 
		else {
		  updateMarkerAddress(notfound);
		}
	});
}

function updateMarkerPosition(pos) {
	jQuery('#jform_latitude').val(pos.lat());
	jQuery('#jform_longitude').val(pos.lng());
}

function updateMarkerAddress(str) {
	if ( !(jQuery("#lockaddress").hasClass('active')) ){
		jQuery('#jform_address').val(str);
	}
}


function initialize() {
	if(jQuery('#jform_latitude').val())
		Lat = jQuery('#jform_latitude').val();
	if(jQuery('#jform_longitude').val())
		Lng = jQuery('#jform_longitude').val();
	
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
	reverseGeocodePosition(center);

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
		reverseGeocodePosition(marker.getPosition());
	});

	infowindow.open(map, marker);
}
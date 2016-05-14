var map;
var marker;
var	infowindow = new google.maps.InfoWindow({
		content: ''
	});
var geocoder = new google.maps.Geocoder();

jQuery(document).ready(function() {

	document.formvalidator.setHandler('boundaries', function(value) {
		return insideBoundaries();
	});

	jQuery( "#locateposition" ).click(function() {
	  // Try HTML5 geolocation
	  infowindow.setContent('Locating your position...<br /><span style="color: red">Please wait</span>');
	  if(navigator.geolocation) {
	    navigator.geolocation.getCurrentPosition(function(position) {
	      var pos = new google.maps.LatLng(position.coords.latitude,
	                                       position.coords.longitude);

		  infowindow.setContent('Your approximate location');
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

	//initially lock address for existing records
	if(itemId > 0)
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

function insideBoundaries()
{

	//return map.getBounds().contains(marker.getPosition());

	if(typeof boundaries != 'undefined') {
		var b=0;
		for (var i = 0; i < boundaries.length; i++) {

			var bounds = new google.maps.Polygon({
				paths: boundaries[i]
			});
			if(google.maps.geometry.poly.containsLocation(marker.getPosition(), bounds))
			{
				b++;
			}

		}

		if(b==0)
		{
			return false;
		}
		return true;
	}
	return true;

}

function codeAddress() {
	var address = jQuery('#'+addrfield).val() + hiddenterm;
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
				html += '<a style="cursor: pointer;" onclick="applySearchResult('+lat+','+lng+',\''+addr+'\');jQuery(\'#IMC_searchModal\').modal(\'hide\');">'+addr+'</a>';
				html += '</li>';
			};
			html += '</ul>';
			jQuery('#searchBody').html(html);
    		jQuery('#IMC_searchModal').modal('show');
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
	jQuery('#'+latfield).val(lat);
	jQuery('#'+lngfield).val(lng);
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
	jQuery('#'+latfield).val(pos.lat());
	jQuery('#'+lngfield).val(pos.lng());
}

function updateMarkerAddress(str) {
	if ( !(jQuery("#lockaddress").hasClass('active')) ){
		jQuery('#'+addrfield).val(str);
	}
}


function initialize() {
	if(jQuery('#'+latfield).val())
		Lat = jQuery('#'+latfield).val();
	if(jQuery('#'+lngfield).val())
		Lng = jQuery('#'+lngfield).val();
	
	var center = new google.maps.LatLng(Lat, Lng);

	var mapOptions = {
		scrollwheel: scrollwheel,
		center: center,
		zoom: zoom
	}
	map = new google.maps.Map(document.getElementById('imc-map-canvas'), mapOptions);

	// Construct the polygons.
	if(typeof boundaries != 'undefined') {

		for (var i = 0; i < boundaries.length; i++) {
			var bounds = new google.maps.Polygon({
				paths: boundaries[i],
				strokeColor: '#FF0000',
				strokeOpacity: 0.8,
				strokeWeight: 2,
				fillColor: '#FF0000',
				fillOpacity: 0.05
			});
			bounds.setMap(map);
		}
	}

	if(disabled){
		marker = new google.maps.Marker({
			position: center,
			animation: google.maps.Animation.DROP,
		});
	}
	else {
		marker = new google.maps.Marker({
			position: center,
			animation: google.maps.Animation.DROP,
			draggable: true
		});
	}
	if(icon != ''){
		marker.setIcon(icon);
	}
	marker.setMap(map);

	infowindow = new google.maps.InfoWindow({
		content: info+'<br />'+(itemId > 0 ? info_unlock : '')
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

	if(!disabled)
		infowindow.open(map, marker);
}
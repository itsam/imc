function initialize() {
	var center = new google.maps.LatLng(Lat, Lng);

	var mapOptions = {
		center: center,
		zoom: zoom
	}
	var map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

	var marker = new google.maps.Marker({
		position: center,
		animation: google.maps.Animation.DROP,
		draggable: true
	});
	marker.setMap(map);

	var infowindow = new google.maps.InfoWindow({
		content: info
	});	
	infowindow.open(map, marker);
}
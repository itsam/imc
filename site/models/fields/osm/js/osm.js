var geocoder = L.Control.Geocoder.nominatim();
var map;
var marker;

function initialize() {
  if (jQuery("#" + latfield).val()) Lat = jQuery("#" + latfield).val();
  if (jQuery("#" + lngfield).val()) Lng = jQuery("#" + lngfield).val();

  var mapOptions = {
    center: [Lat, Lng],
    zoom: 18,
  };

  map = L.map("imc-map-canvas", mapOptions);

  // Add OpenStreetMap tile layer
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(map);

  marker = new L.Marker([Lat, Lng]);
  marker.addTo(map);
}

function codeAddress() {
  var address = jQuery("#" + addrfield).val() + hiddenterm;
  geocoder.geocode(address, function (results) {
    if (results.length > 1) {
      // make list with results
      var html = "<ul>";
      for (var i = 0; i < results.length; i++) {
        var lat = results[i].center.lat;
        var lng = results[i].center.lng;
        var addr = results[i].name;
        html +=
          '<li><a style="cursor: pointer;" onclick="applySearchResult(' +
          lat +
          "," +
          lng +
          ",'" +
          addr +
          "');jQuery('#IMC_searchModal').modal('hide');\">" +
          addr +
          "</a></li>";
      }
      html += "</ul>";
      jQuery("#searchBody").html(html);
      jQuery("#IMC_searchModal").modal("show");
    } else if (results.length === 1) {
      applySearchResult(
        results[0].center.lat,
        results[0].center.lng,
        results[0].name
      );
    } else {
      jQuery("#searchBody").html("<h2>" + notfound + "</h2>");
      jQuery("#searchModal").modal("show");
    }
  });
}

function applySearchResult(lat, lng, addr) {
  var pos = L.latLng(lat, lng);
  map.setView(pos, 13);
  marker.setLatLng(pos);
  jQuery("#" + latfield).val(lat);
  jQuery("#" + lngfield).val(lng);
  updateMarkerAddress(addr);
}

function updateMarkerAddress(str) {
  if (!jQuery("#lockaddress").hasClass("active")) {
    jQuery("#" + addrfield).val(str);
  }
}

jQuery(document).ready(function () {
  jQuery("#searchaddress").click(function () {
    codeAddress();
  });
});

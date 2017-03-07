$(document).ready(function() {
	
	$('.toggleMap').click(function() {
	    $('#mapaWrapper').toggle();
	    
	    if($(this).html() === 'Mostrar Mapa'){
	        $(this).html('Ocultar Mapa');
	    }
	    else {
	        $(this).html('Mostrar Mapa');
	    }
	});

	launchMap();

});

function addMarker(loc, map){
	var marker = new google.maps.Marker({
		position: loc,
		map: map,
		animation: google.maps.Animation.DROP
	});
	return marker;
}

function launchMap(){
	var geocoder = new google.maps.Geocoder();

	var myLatLng = { lat: -33.451190, lng: -70.654388 };

	// Create a map object and specify the DOM element for display.
	map = new google.maps.Map(document.getElementById('map'), {
            center: myLatLng,
            scrollwheel: false,
            zoom: 10
	});

	var marker = new google.maps.Marker({
		map: map,
		anchorPoint: new google.maps.Point(0, -29)
	});

	var infowindow = new google.maps.InfoWindow();
	var autocomplete = new google.maps.places.Autocomplete(document.getElementById('Form-field-Evento-direccion'));

	autocomplete.addListener('place_changed', function() {
		infowindow.close();
		marker.setVisible(false);
		var place = autocomplete.getPlace();

		if (!place.geometry) {
			window.alert("Autocomplete's returned place contains no geometry");
			return;
		}

		// If the place has a geometry, then present it on a map.
		if (place.geometry.viewport) {
			map.fitBounds(place.geometry.viewport);
		} else {
			map.setCenter(place.geometry.location);
			map.setZoom(20); 
		}

		marker.setPosition(place.geometry.location);
		marker.setVisible(true);

		var address = '';
		if (place.address_components) {
			address = [
				(place.address_components[0] && place.address_components[0].short_name || ''),
				(place.address_components[1] && place.address_components[1].short_name || ''),
				(place.address_components[2] && place.address_components[2].short_name || '')
			].join(' ');
		}

		infowindow.setContent('<div><strong>' + place.name + '</strong><br>' + address);
		infowindow.open(map, marker);
	});

	$.ajax({
		url: '/backend/anguro/capse/eventos/getUsuariosGeocodes',
		type: 'POST',
		cache: false,
		success: function(data){
                    if(data !== undefined && data.n > 0){
                        data.geocodes.forEach(function(el) {
                            addMarker(el.geocode.location, map);
                        }, this);
                    }
                    else {
                        console.warn('0 Direcciones registradas');
                    }
		}
	});

	markerSelected = null;

	map.addListener('click', function(e) {
		if(markerSelected != null){
			markerSelected.setMap(null);
		}

		this.setOptions({
			scrollwheel:true 
		});

		markerSelected = addMarker(e.latLng, map);
		geocodePosition(markerSelected.getPosition());
	});

	function geocodePosition(pos) {
		geocoder.geocode({
			latLng: pos
		}, function(responses) {
			if (responses && responses.length > 0) {
				actualizaAutocomplete(responses[0].formatted_address);
			} else {
				actualizaAutocomplete('Cannot determine address at this location.');
			}
		});
	}

	function actualizaAutocomplete(dir){
		document.getElementById('Form-field-Evento-direccion').value = dir;
	}
}
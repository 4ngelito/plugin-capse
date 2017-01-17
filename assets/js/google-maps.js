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

function addMarker(geocode, titulo, map){
	var marker = new google.maps.Marker({
		position: geocode.location,
		map: map,
		title: titulo
	});
	return marker;
}

function launchMap(){
	var myLatLng = { lat: -33.451190, lng: -70.654388 };

	// Create a map object and specify the DOM element for display.
	var map = new google.maps.Map(document.getElementById('map'), {
		center: myLatLng,
		scrollwheel: false,
		zoom: 10
	});
	
	$.ajax({
		url: 'getUsuariosGeocodes',
		type: 'POST',
		cache: false,
		success: function(data){	
			data.forEach(function(el) {
				addMarker(el.geocode, el.geocode.titulo, map);
			}, this);
		}
	});
}
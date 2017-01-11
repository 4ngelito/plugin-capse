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
});
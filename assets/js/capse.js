$(document).ready(function() {
    var owl = $('.formularios');
    owl.owlCarousel({
        nav: false,
        items: 1,
        center: false,
        dots: false,
        mouseDrag: false
    });
    
    $('.registrarse').click(function() {
        owl.trigger('next.owl.carousel');
    });
    
    $('.iniciarSesion').click(function() {
        owl.trigger('prev.owl.carousel', [300]);
    });
    
    $('.edit-tooltip').tooltip({
    	delay: 50,
    	position: 'bottom',
    	tooltip: 'Click para Editar'
    });
    
    $('.collapsible').collapsible();    
    
    
    var fecha = $('#fecha_nacimiento').data('fecha');
    var $datepicker = $('.datepicker');
    $datepicker.pickadate({
        
        monthsFull: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
        monthsShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
        weekdaysFull: ['Dom', 'Lun', 'Mar', 'Mi\u00E9', 'Jue', 'Vie', 'S\u00E1b'],
        weekdaysShort: ['Dom', 'Lun', 'Mar', 'Mi\u00E9', 'Jue', 'Vie', 'S\u00E1b'],
        
        showWeekdaysFull: true,
        
        selectMonths: true,
        selectYears: 60,
        max: true,

        firstDay: 1,
        
        today: 'Hoy',
        clear: 'Limpiar',
        close: 'Cerrar',

        labelMonthNext: 'Mes Sig,',
        labelMonthPrev: 'Mes Ant.',
        labelMonthSelect: 'Seleccione Mes',
        labelYearSelect: 'Seleccione Año',

        format: 'd mmmm yyyy',
        formatSubmit: 'yyyy-m-d',
        hiddenSuffix: '',

        container: '.formDate',
        
        onOpen :function() { 
            $(this).appendTo('body');
        }
    });
    
    if(fecha !== undefined){
        var picker = $datepicker.pickadate('picker');
        picker.set('select', fecha, { format: 'yyyy-mm-dd' });
    }
    
    var $telefonoInput = '<div class="telefono row valign-wrapper"> \
            <div class="input-field col s3">              \
              <label for="telefonos[tipo][]" class="active">Tipo</label> \
              <select name="telefonos[tipo][]" required> \
                  <option value="" disabled selected>-- Seleccione --</option> \
                  <option value="movil">Móvil</option> \
                  <option value="trabajo" >Trabajo</option> \
                  <option value="casa" >Casa</option> \
              </select>     \
            </div> \
            <div class="input-field col s8"> \
                <label for="telefonos[numero][]">Número</label> \
                <input type="text" class="form-control validate" name="telefonos[numero][]" placeholder="+56912345678" required/> \
            </div> \
            <a class="btn-flat col s1 valign red-text quitar"><i class="material-icons">clear</i></a> \
        </div>' ;
    
    var $pacienteInput = '<div class="paciente row valign-wrapper"> \
            <div class="input-field col s6">               \
              <label for="pacientes[parentesco][]" class="active">Parentesco</label> \
              <select name="pacientes[parentesco][]"> \
                  <option value="" >-- Ninguno --</option> \
                  <option value="familiar" >Familiar</option> \
                  <option value="amigo" >Amigo</option> \
                  <option value="otro" >Otro</option> \
              </select>      \
            </div> \
            <div class="input-field col s6">               \
              <label for="pacientes[sexo][]" class="active">Sexo</label> \
              <select name="pacientes[sexo][]" required> \
                  <option value="" disabled>-- Seleccione --</option> \
                  <option value="masculino" >Masculino</option> \
                  <option value="femenino" >Femenino</option> \
                  <option value="otro" >Otro</option> \
              </select>      \
            </div> \
            <a class="btn-flat col s1 valign red-text quitar"><i class="material-icons">clear</i></a> \
        </div>';
    
    $(document.body).on('click', '.quitar', function() {
        $(this).parent().fadeOut().remove();
    });
    
    $(document.body).on('click', '.agregar', function() {
        if($(this).data('tipo') === 'telefono'){
            $(this).before($telefonoInput);
        }
         if($(this).data('tipo') === 'paciente'){
            $(this).before($pacienteInput);
        }
        $('select').material_select();
    });

    $('#avatar_file').change(function(){
      var form = $('#avatarForm');
      var d = new FormData(form[0]);

      $.ajax({
          headers: {
           'X-OCTOBER-REQUEST-HANDLER': form.data('request'), // important
         },
          //url: form.attr('action'),
          data: d,
          processData: false,
          contentType: false,
          type: 'POST',
          cache: false,
          success: function(data){
            var res = data.result;
            if(res.response === 'success'){
              var imagen = res.imagen;
              var img = '<img class="circle responsive-img" src="' + imagen.avatar + '" title="' + imagen.titulo + '" alt="' + imagen.descripcion +'">';
              $('#avatarImagen').html(img);
            }

            $.request('onDummy',{
              update : {'cuentaUsuario::flash_message': '#flash_message'}
              })
            ;
          }
      });
    });

    $('.editable').focusout(function(){
      var campo = $(this).data('campo');
      var valor = $(this).html();
      var $input = $('input[name=' + campo + ']');
      $input.val(valor);
      if(campo === 'email'){
        $.request('onCheckEmail',{
            data : {'email': valor},
            update : {'cuentaUsuario::flash_message': '#flash_message'}
            })
          ;
      }
    });
    
    $(window).on('ajaxErrorMessage', function(event, message){
        $.oc.flashMsg({
            'text': message,
            'class': 'error',
            'interval': 3
        });        
        // This will stop the default alert() message
        event.preventDefault();
    })
        
});

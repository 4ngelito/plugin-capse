<?php return [
    'plugin' => [
        'name' => 'capse',
        'description' => 'plugin para la pataforma de capse',
    ],
    'cuenta' => [
        'cuenta' => 'Cuenta Cuidador',
        'success_saved' => 'Datos actualizados correctamente.'
    ],
    'evento' => [
        'evento' => 'Evento',
        'descripcion' => 'Muestra el evento',
        
        'menu_label' => 'Eventos',
        'titulo' => 'Titulo',
        'titulo_placeholder' => 'Nombre del Evento',
        'slug' => 'Slug',
        'slug_placeholder' => 'slug-evento',
        'tab_edit' => 'Descripción',
        'tab_manage' => 'Más Información',

        'published' => 'Publicado?',
        'cuando' => 'cuando es?',
        'featured_images' => 'Imagenes destacadas',
        'created' => 'Creado',
        'updated' => 'Actualizado',
        'new_evento' => 'Crear evento',
        'eventos' => 'Eventos',

        'tab' => 'permisos eventos',
        'access_eventos' => 'acceso eventos',
        'acces_other_eventos' => 'acceso eventos de otros',
        'close_confirm' => 'Hay cambios sin guardar\\nEstá seguro que desea salir?',
        'return_to_eventos' => 'Volver al listado de Eventos',
        'published_validation' => 'Publicacion validada',
        'delete_confirm' => ' Está seguro que desea eliminar el Evento?',
        'direccion' => 'Donde?',
        'direccion_comment' => 'Seleccione un punto en el mapa para definir la dirección'
    ],
    'eventos' => [
        'eventos' => 'Eventos',
        'descripcion' => 'Lista los eventos disponibles.',
        'filter_date' => 'entre fechas',
        'eliminacion_masiva' => 'Los eventos fueron eliminados.'
    ],
    'menuitem' => [
        'capse-evento' => 'evento',
        'capse-eventos' => 'todos los eventos'
    ],
    'user' => [
        'rut' => 'Rut',
        'fecha_nacimiento' => 'Fecha de Nacimiento',
        'sexo' => 'Sexo',
        'telefonos' => 'Teléfonos',
        'telefono' => 'Número de Teléfono',
        'direccion' => 'Dirección',
        'region' => 'Región',
        'provincia' => 'Provincia',
        'comuna' => 'Comuna',
        'pacientes' => 'Pacientes'
    ],
    'formularios' => [
        'sign_in' => 'Iniciar Sesión',
        'register' => 'Registro'
    ],
    'messages' => [
        'imagen_invalida' => 'Error: El formato de imagen seleccionado no está permitido!',
        'deactivate_account' => 'Desactivar la cuenta',
        'email_usado' => 'El email indicado ya está en uso',
        'password_missmatch' => 'Las contraseñas no coinciden!'

    ],
    'permissions' => [
        'settings' => 'Administrar Plugin',
    ],
    'socio' => [
        'socio' => 'Socios',
        'description' => 'Imagenes de Socios registrados',
        
        'nombre' => 'Nombre',
        'nombre_descripcion' => 'Nombre del Socio',
        'url' => 'Sitio Web',
        'url_descripcion' => 'URL del sitio web',
        'imagen' => 'Imagen',
        'imagen_descripcion' => 'Imagen a mostrar',
        'created' => 'Registrado el',
        'updated' => 'Actualizado el',
        
        'menu_label' => 'Socios',
        'new_socio' => 'Nuevo Socio',
        'socios' => 'Socios',
        
        'tab' => 'Permisos de Socios',
        'access_socios' => 'Acceso a los socios registrados'
    ],
    
    'settings' => [
        
        'eventos_pagination' => 'Page number',
        'eventos_pagination_description' => 'This value is used to determine what page the user is on.',
        'eventos_per_page' => 'Posts per page',
        'eventos_per_page_validation' => 'Invalid format of the eventos per page value',
        'eventos_no_eventos' => 'No eventos message',
        'eventos_no_eventos_description' => 'Message to display in the blog post list in case if there are no eventos. This property is used by the default component partial.',
        'eventos_order' => 'Post order',
        'eventos_order_description' => 'Attribute on which the eventos should be ordered',
        'eventos_post' => 'Post page',
        'eventos_post_description' => 'Name of the blog post page file for the "Learn more" links. This property is used by the default component partial.',
        
        'menu_label' => 'Location settings',
        'menu_description' => 'Manage location based settings.',
        'google_maps_key' => 'Google Maps API Key',
        'google_maps_key_comment' => 'If you plan on using Google Maps services, enter the API key for it here.',
        
        'evento_slug' => 'Slug del Evento',
        'evento_slug_description' => 'Look up the blog post using the supplied slug value.',
        
        'socio_no_socio' => 'Mensaje sin Socio',
        'socio_no_socio_description' => 'Mensaje de ningun socio registrado'
        
    ],
    'cuidados' => [
        'create_cuidado' => 'Nuevo Cuidado',
        'list_title' => 'Cuidados',
        'new_cuidado' => 'Nuevo Cuidado',
        'cuidados' => 'Cuidados',
        'menu_label' => 'Cuidados'
    ],
    'cuidado' => [
        'return_to_cuidados' => 'Volver al listado de Cuidados'
    ],
    'autocuidados' => [
        'create_autocuidado' => 'Nuevo Autocuidado',
        'list_title' => 'Autocuidados',
        'new_autocuidado' => 'Nuevo Autocuidado',
        'autocuidados' => 'Autocuidados',
        'menu_label' => 'Autocuidados'
    ],
    'autocuidado' => [
        'return_to_autocuidados' => 'Volver al listado de Autocuidados'
    ],
    'faqs' => [
        'menu_label' => 'Preg. Frec.',
        'new_faq' => 'Nueva Pregunta',
        'faqs' => 'Preguntas Frecuentes',
        
        'access_faqs' => 'Acceso a las preguntas frecuentes registradas',
        'faqs' => 'Preguntas Frecuentes',
        'descripcion' => 'Muestra el listado de preguntas con sus respuestas'
    ],
    'faq' => [
        'pregunta' => 'Pregunta',
        'created' => 'Fecha Creado',
        'respuesta' => 'Respuesta'
    ]
];
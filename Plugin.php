<?php namespace Anguro\Capse;

//use Backend;
use Yaml;
use File;
use Lang;
use Backend;
use Event;
use Auth;
use System\Classes\PluginBase;
use RainLab\User\Models\User as UserModel;
use RainLab\User\Models\UserGroup as UserGroup;
use RainLab\User\Controllers\Users as UsersController;


/**
 * Capse Plugin Information File
 */
class Plugin extends PluginBase
{
    
    public $require = ['RainLab.User'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Capse',
            'description' => 'No description provided yet...',
            'author'      => 'Anguro',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        UserModel::extend(function($model) {
            
            $campos = [
                'rut',
                'fecha_nacimiento',
                'sexo',
                'telefonos',
                'direccion',
                'comuna',
                'provincia',
                'region',
                'pacientes',
                'geocode'
            ];
            
            $model->jsonable(array_merge($model->getJsonable(), ['telefonos', 'pacientes', 'geocode']));
            
            $model->fillable(array_merge($model->getFillable(), $campos));
            
            $model->addDynamicMethod('getRegionOptions', function() {
                return $this->getRegiones();
            });

            $model->addDynamicMethod('getProvinciaOptions', function() use ($model) {
                return $this->getProvincias($model->region);
            });
            
            $model->addDynamicMethod('getComunaOptions', function() use ($model) {
                return $this->getComunas($model->provincia);
            });
            
            $model->addDynamicMethod('addUserGroup', function($group) use ($model) {
                if ($group instanceof Collection) {
                   return $model->groups()->saveMany($group);
                }

                if (is_string($group)) {
                   $group = UserGroup::whereCode($group)->first();

                   return $model->groups()->save($group);
                }

                if ($group instanceof UserGroup) {
                   return $model->groups()->save($group);
                }
            });

            $model->addDynamicMethod('getDireccionCompleta', function() use ($model) {
                return $this->getDireccionCompleta();
            });
        
        });

        UsersController::extendFormFields(function($widget, $model, $context) {
            if(!$model instanceof UserModel){
                return;
            }
            
            if (starts_with($widget->arrayName, "User[telefonos]") || starts_with($widget->arrayName, "User[pacientes]")) {
                return;
            }
            
            $configFile = __DIR__ . '/config/cuidadores_fields.yaml';
            $config = Yaml::parse(File::get($configFile));
            $widget->addTabFields($config);
        });

        /*
         * Register menu items for the RainLab.Pages plugin
         */
        Event::listen('pages.menuitem.listTypes', function() {
            return [
                'capse-evento' => 'anguro.capse::lang.menuitem.capse-evento',
                'capse-eventos' => 'anguro.capse::lang.menuitem.capse-eventos',
            ];
        });

        Event::listen('pages.menuitem.getTypeInfo', function($type) {
            if ($type == 'capse-evento' || $type == 'capse-eventos') {
                return Evento::getMenuTypeInfo($type);
            }
        });

        Event::listen('pages.menuitem.resolveItem', function($type, $item, $url, $theme) {
            if ($type == 'capse-evento' || $type == 'capse-eventos') {
                return Evento::resolveMenuItem($item, $url, $theme);
            }
        });

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {

        return [
            'Anguro\Capse\Components\Account' => 'cuentaUsuario',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'anguro.capse.access_eventos' => [
                'tab'   => 'anguro.capse::lang.evento.tab',
                'label' => 'anguro.capse::lang.evento.access_eventos'
            ],
            'anguro.capse.access_other_eventos' => [
                'tab'   => 'anguro.capse::lang.evento.tab',
                'label' => 'anguro.capse::lang.evento.access_other_eventos'
            ]
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return [
            'evento' => [
                'label'       => 'anguro.capse::lang.evento.menu_label',
                'url'         => Backend::url('anguro/capse/eventos'),
                'icon'        => 'icon-pencil',
                'permissions' => ['anguro.capse.*'],
                'order'       => 30,

                'sideMenu' => [
                    'new_evento' => [
                        'label'       => 'anguro.capse::lang.evento.new_evento',
                        'icon'        => 'icon-plus',
                        'url'         => Backend::url('anguro/capse/eventos/create'),
                        'permissions' => ['anguro.capse.access_eventos']
                    ],
                    'eventos' => [
                        'label'       => 'anguro.capse::lang.evento.eventos',
                        'icon'        => 'icon-copy',
                        'url'         => Backend::url('anguro/capse/eventos'),
                        'permissions' => ['anguro.capse.access_eventos']
                    ]
                ]
            ]
        ];
    }


    /**
     * Register new Twig variables
     * @return array
     */
    public function registerMarkupTags()
    {
        return [
            'functions' => [
                '_' => function($messageId, $domain = 'anguro.capse::lang.messages') {
                    return Lang::get("$domain.$messageId");
                }
            ]
        ];
    }
    
    private function getRegiones(){
        $jsonFile = __DIR__ . '/assets/js/bdcut-cl/BDCUT_CL_Regiones.min.json';
        $json = json_decode(File::get($jsonFile), false, 512, JSON_UNESCAPED_UNICODE);
        
        $regiones = null;
        foreach ($json as $region) {
            $regiones[$region->region_id] = $region->name;
        }
        
        return $regiones;
    }
    
    private function getProvincias($region){
        if($region == NULL)
            return [ '' => '-- Seleccione Regi&oacute;n --'];
        $jsonFile = __DIR__ . '/assets/js/bdcut-cl/BDCUT_CL_ProvinciaRegion.min.json';
        $json = json_decode(File::get($jsonFile), false, 512, JSON_UNESCAPED_UNICODE);
        $provincias = null;
        
        foreach ($json as $region_id => $p) {
            if($region_id == $region){
                foreach ($p as $prov) {
                    $provincias[$prov->provincia_id] = $prov->name;
                }
            }
        }
        
        return $provincias;
    }
    
    private function getComunas($provincia){
        if($provincia == NULL)
            return [ '' => '-- Seleccione Provincia --'];
        $jsonFile = __DIR__ . '/assets/js/bdcut-cl/BDCUT_CL_ComunaProvincia.min.json';
        $json = json_decode(File::get($jsonFile), false, 512, JSON_UNESCAPED_UNICODE);
        $comunas = null;
        
        foreach ($json as $provincia_id => $c) {
            if($provincia_id == $provincia){
                foreach ($c as $comu) {
                    $comunas[$comu->comuna_id] = $comu->name;
                }
            }
        }
        
        return $comunas;
    }

    private function getDireccionCompleta(){
        $pathRegiones = __DIR__ . '/assets/js/bdcut-cl/BDCUT_CL_Regiones.min.json';
        $pathProvincias = __DIR__ . '/assets/js/bdcut-cl/BDCUT_CL_ProvinciaRegion.min.json';
        $pathComunas = __DIR__ . '/assets/js/bdcut-cl/BDCUT_CL_ComunaProvincia.min.json';

        $jsonRegiones = json_decode(File::get($pathRegiones), false, 512, JSON_UNESCAPED_UNICODE);
        $jsonProvincias = json_decode(File::get($pathProvincias), false, 512, JSON_UNESCAPED_UNICODE);
        $jsonComunas = json_decode(File::get($pathComunas), false, 512, JSON_UNESCAPED_UNICODE);
        
        $regiones = [];
        $provincias = [];
        $comunas = [];

        foreach ($jsonRegiones as $region) {
            $regiones[$region->region_id] = $region->name;
        }

        foreach ($jsonProvincias as $region_id => $p) {
            //if($region_id == $region){
                foreach ($p as $prov) {
                    $provincias[$region_id][$prov->provincia_id] = $prov->name;
                }
            //}
        }

        foreach ($jsonComunas as $provincia_id => $c) {
            //if($provincia_id == $provincia){
                foreach ($c as $comu) {
                    $comunas[$provincia_id][$comu->comuna_id] = $comu->name;
                }
            //}
        }

        $u = Auth::getUser();

        $direccion = [
            'direccion' => $u->direccion,
            'region' => $regiones[$u->region],
            'provincia' => $provincias[$u->region][$u->provincia],
            'comuna' => $comunas[$u->provincia][$u->comuna]
        ];

        unset($regiones);
        unset($provincias);
        unset($comunas);
        
        return $direccion['direccion'] .', '. $direccion['comuna'] .', '. $direccion['provincia'] .', '. $direccion['region'];
    }
    

}


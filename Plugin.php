<?php namespace Anguro\Capse;

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
use Anguro\Capse\Classes\DireccionManager as Direccion;


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
                return Direccion::getRegiones();
            });

            $model->addDynamicMethod('getProvinciaOptions', function() use ($model) {
                return Direccion::getProvincias($model->region);
            });
            
            $model->addDynamicMethod('getComunaOptions', function() use ($model) {
                return Direccion::getComunas($model->provincia);
            });
            
            $model->addDynamicMethod('addUserGroup', function($group) use ($model) {
                if ($group instanceof Collection) {
                   return $model->groups()->saveMany($group);
                }

                if (is_string($group)) {
                    $str = $group;
                    $group = UserGroup::whereCode($group)->first();
                    if(!$group){
                        $g = new UserGroup;
                        $g->name = ucwords($str);
                        $g->code = urlencode($str);
                        $g->save();
                        $group = $g;
                    }

                   return $model->groups()->save($group);
                }

                if ($group instanceof UserGroup) {
                   return $model->groups()->save($group);
                }
            });

            $model->addDynamicMethod('getDireccionCompleta', function() use ($model) {
                return $this->getDireccionCompleta($model);
            });

            $model->addDynamicMethod('setGeocode', function() use ($model) {
                return $this->setUserGeocode($model);
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

        Event::listen('eloquent.updating: RainLab\User\Models\User', function($model) {
            
            if($model->getOriginal('direccion') !== $model->direccion && strlen($model->direccion) > 3){
                $model->setGeocode();
            }

            return true;
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
            'Anguro\Capse\Components\Eventos' => 'eventos',
            'Anguro\Capse\Components\Evento' => 'evento',
            'Anguro\Capse\Components\Patrocinador' => 'patrocinadores',
        ];
    }
    
    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'anguro.capse::lang.settings.menu_label',
                'description' => 'anguro.capse::lang.settings.menu_description',
                'category'    => 'anguro.capse::lang.plugin.name',
                'icon'        => 'icon-cog',
                'class'       => 'Anguro\Capse\Models\Setting',
                'order'       => 500,
                'permissions' => ['anguro.capse.access_settings'],
            ]
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
            ],
            'anguro.capse.access_settings' => [
                'tab'   => 'anguro.capse::lang.plugin.name',
                'label' => 'anguro.capse::lang.permission.settings'
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
            ],
            'patrocinador' => [
                'label'       => 'anguro.capse::lang.patrocinador.menu_label',
                'url'         => Backend::url('anguro/capse/patrocinadores'),
                'icon'        => 'icon-pencil',
                'permissions' => ['anguro.capse.*'],
                'order'       => 30,

                'sideMenu' => [
                    'new_patrocinador' => [
                        'label'       => 'anguro.capse::lang.patrocinador.new_patrocinador',
                        'icon'        => 'icon-plus',
                        'url'         => Backend::url('anguro/capse/patrocinadores/create'),
                        'permissions' => ['anguro.capse.*']
                    ],
                    'patrocinador' => [
                        'label'       => 'anguro.capse::lang.patrocinador.patrocinadores',
                        'icon'        => 'icon-copy',
                        'url'         => Backend::url('anguro/capse/patrocinadores'),
                        'permissions' => ['anguro.capse.*']
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
                },
                'getUserAvatar' => function ($size){
                    return Auth::getUser()->getAvatarThumb($size, ['mode' => 'crop']);
                }
            ]
        ];
    } 
    

    private function getDireccionCompleta($model){

        $jsonRegiones = Direccion::leeRegiones();
        $jsonProvincias = Direccion::leeProvincias();
        $jsonComunas = Direccion::leeComunas();
        
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

        if(!isset($model) || strlen($model->direccion) <= 0){
            return ;
        }    

        $direccion = [
            'direccion' => $model->direccion,
            'region' => $regiones[$model->region],
            'provincia' => $provincias[$model->region][$model->provincia],
            'comuna' => $comunas[$model->provincia][$model->comuna]
        ];

        unset($regiones);
        unset($provincias);
        unset($comunas);
        
        return $direccion['direccion'] .', '. $direccion['comuna'] .', '. $direccion['provincia'] .', '. $direccion['region'];
    }

    private function setUserGeocode($model){
        $dir = new Direccion();
        $direccion = urlencode($model->getDireccionCompleta());
        
        $g = $dir->getGeocode($direccion);

        if($g !== null){
            $model->geocode = $g;
        }

        return true;        
    }
    

}


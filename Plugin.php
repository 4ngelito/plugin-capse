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
use Rainlab\Blog\Models\Post as PostModel;
use Rainlab\Blog\Models\Category as CategoryModel;
use Anguro\Capse\Controllers\Cuidados as CuidadosController;
use Anguro\Capse\Controllers\Autocuidados as AutocuidadosController;
use Anguro\Capse\Classes\DireccionManager as Direccion;


/**
 * Capse Plugin Information File
 */
class Plugin extends PluginBase
{
    
    public $require = ['RainLab.User', 'Rainlab.Blog'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Capse',
            'description' => 'Plugin creado para el sitio de CuidadoresUnidos.cl',
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
        PostModel::extend(function($model){
            $model->addDynamicMethod('asignaCategoria', function($categoria) use ($model) {
                $c = $model->categories()->first();
                if(!$c){
                    $cat = $this->getCategory($categoria);
                    return $model->categories = [$cat];
                }
                return $c;
            });
        });
        
        CuidadosController::extendFormFields(function($widget, $model, $context) {
            if(!$model instanceof PostModel){
                return;
            }
            $widget->removeField('categories');
            $widget->removeField('excerpt');
            $model->asignaCategoria('Cuidados');
        });
        
        AutocuidadosController::extendFormFields(function($widget, $model, $context) {
            if(!$model instanceof PostModel){
                return;
            }
            $widget->removeField('categories');
            $widget->removeField('excerpt');
            $model->asignaCategoria('Autocuidados');
        });
        
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
                        $g->code = str_slug($str);
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
        
        /**
         * Quita elementos del menu
         */
        Event::listen('backend.menu.extendItems', function($manager) {

            $manager->removeMainMenuItem('October.Cms', 'cms');
            $manager->removeMainMenuItem('October.Cms', 'media');
            $manager->removeMainMenuItem('Rainlab.Blog', 'blog');

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
            'Anguro\Capse\Components\Socios' => 'socios',
            'Anguro\Capse\Components\Preguntas' => 'preguntasFrecuentes',
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
                'tab'   => 'anguro.capse::lang.plugin.name',
                'label' => 'anguro.capse::lang.evento.access_eventos'
            ],
            'anguro.capse.access_other_eventos' => [
                'tab'   => 'anguro.capse::lang.plugin.name',
                'label' => 'anguro.capse::lang.evento.access_other_eventos'
            ],
            'anguro.capse.access_socios' => [
                'tab'   => 'anguro.capse::lang.plugin.name',
                'label' => 'anguro.capse::lang.socio.access_socios'
            ],
            'anguro.capse.access_settings' => [
                'tab'   => 'anguro.capse::lang.plugin.name',
                'label' => 'anguro.capse::lang.permission.settings'
            ],
            'anguro.capse.access_cuidados' => [
                'tab'   => 'anguro.capse::lang.plugin.name',
                'label' => 'anguro.capse::lang.permission.cuidados'
            ],
            'anguro.capse.access_autocuidados' => [
                'tab'   => 'anguro.capse::lang.plugin.name',
                'label' => 'anguro.capse::lang.permission.autocuidados'
            ],
            'anguro.capse.access_faq' => [
                'tab'   => 'anguro.capse::lang.plugin.name',
                'label' => 'anguro.capse::lang.permission.faq'
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
            'eventos' => [
                'label'       => 'anguro.capse::lang.evento.menu_label',
                'url'         => Backend::url('anguro/capse/eventos'),
                'icon'        => 'icon-calendar-check-o',
                'iconSvg'     => 'plugins/anguro/capse/assets/images/eventos-icon.png',
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
            'socios' => [
                'label'       => 'anguro.capse::lang.socio.menu_label',
                'url'         => Backend::url('anguro/capse/socios'),
                'icon'        => 'icon-users',
                'iconSvg'     => 'plugins/anguro/capse/assets/images/socios-icon.png',
                'permissions' => ['anguro.capse.*'],
                'order'       => 30,

                'sideMenu' => [
                    'new_socio' => [
                        'label'       => 'anguro.capse::lang.socio.new_socio',
                        'icon'        => 'icon-plus',
                        'url'         => Backend::url('anguro/capse/socios/create'),
                        'permissions' => ['anguro.capse.access_socios']
                    ],
                    'socio' => [
                        'label'       => 'anguro.capse::lang.socio.socios',
                        'icon'        => 'icon-copy',
                        'url'         => Backend::url('anguro/capse/socios'),
                        'permissions' => ['anguro.capse.access_socios']
                    ]
                ]
            ],
            'cuidados' => [
                'label'       => 'anguro.capse::lang.cuidados.menu_label',
                'url'         => Backend::url('anguro/capse/cuidados'),
                'icon'        => 'icon-medkit',
                'iconSvg'     => 'plugins/anguro/capse/assets/images/cuidados-icon.png',
                'permissions' => ['anguro.capse.*'],
                'order'       => 30,

                'sideMenu' => [
                    'new_post' => [
                        'label'       => 'anguro.capse::lang.cuidados.new_cuidado',
                        'icon'        => 'icon-plus',
                        'url'         => Backend::url('anguro/capse/cuidados/create'),
                        'permissions' => ['anguro.capse.access_cuidados']
                    ],
                    'posts' => [
                        'label'       => 'anguro.capse::lang.cuidados.cuidados',
                        'icon'        => 'icon-copy',
                        'url'         => Backend::url('anguro/capse/cuidados'),
                        'permissions' => ['anguro.capse.access_cuidados']
                    ]
                ]
            ],
            'autocuidados' => [
                'label'       => 'anguro.capse::lang.autocuidados.menu_label',
                'url'         => Backend::url('anguro/capse/autocuidados'),
                'icon'        => 'icon-heart-o',
                'iconSvg'     => 'plugins/anguro/capse/assets/images/autocuidados-icon.png',
                'permissions' => ['anguro.capse.*'],
                'order'       => 30,

                'sideMenu' => [
                    'new_post' => [
                        'label'       => 'anguro.capse::lang.autocuidados.new_autocuidado',
                        'icon'        => 'icon-plus',
                        'url'         => Backend::url('anguro/capse/autocuidados/create'),
                        'permissions' => ['anguro.capse.access_autocuidados']
                    ],
                    'posts' => [
                        'label'       => 'anguro.capse::lang.autocuidados.autocuidados',
                        'icon'        => 'icon-copy',
                        'url'         => Backend::url('anguro/capse/autocuidados'),
                        'permissions' => ['anguro.capse.access_cuidados']
                    ]
                ]
            ],
            'faqs' => [
                'label'       => 'anguro.capse::lang.faqs.menu_label',
                'url'         => Backend::url('anguro/capse/faqs'),
                'icon'        => 'icon-question-circle-o',
                'iconSvg'     => 'plugins/anguro/capse/assets/images/faqs-icon.png',
                'permissions' => ['anguro.capse.*'],
                'order'       => 30,

                'sideMenu' => [
                    'new_faq' => [
                        'label'       => 'anguro.capse::lang.faqs.new_faq',
                        'icon'        => 'icon-plus',
                        'url'         => Backend::url('anguro/capse/faqs/create'),
                        'permissions' => ['anguro.capse.access_faqs']
                    ],
                    'faqs' => [
                        'label'       => 'anguro.capse::lang.faqs.faqs',
                        'icon'        => 'icon-copy',
                        'url'         => Backend::url('anguro/capse/faqs'),
                        'permissions' => ['anguro.capse.access_faqs']
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
    
    private function getCategory($nombre){
        $cat = CategoryModel::whereSlug(str_slug($nombre))->first();
        if(!$cat instanceof CategoryModel){
            $c = new CategoryModel;
            $c->name = $nombre;
            $c->slug = str_slug($nombre);
            $c->save();
            $cat = $c;
        }
        return $cat;
    }
    

}


<?php namespace Anguro\Capse;

//use Backend;
use Yaml;
use File;
use Lang;
use Storage;
use System\Classes\PluginBase;
use RainLab\User\Models\User as UserModel;
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
                'pacientes'
            ];
            
            $model->jsonable(array_merge($model->getJsonable(), ['telefonos', 'pacientes']));
            
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
        return []; // Remove this line to activate

        return [
            'anguro.capse.some_permission' => [
                'tab' => 'Capse',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'capse' => [
                'label'       => 'Capse',
                'url'         => Backend::url('anguro/capse/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['anguro.capse.*'],
                'order'       => 500,
            ],
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
    

}


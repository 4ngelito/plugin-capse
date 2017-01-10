<?php namespace Anguro\Capse\Controllers;

use BackendMenu;
use File;
use Rainlab\User\Models\User as UserModel;
use Backend\Classes\Controller;
use Anguro\Capse\Models\Evento;

/**
 * Eventos Back-end Controller
 */
class Eventos extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Anguro.Capse', 'capse', 'eventos');
    }

    public function index()
    {
        $this->vars['eventosTotal'] = Evento::count();
        //$this->vars['eventosPublished'] = Evento::isPublished()->count();
        //$this->vars['eventosDrafts'] = $this->vars['eventosTotal'] - $this->vars['eventosPublished'];

        $this->asExtension('ListController')->index();
    }

    public function create()
    {
        BackendMenu::setContextSideMenu('new_evento');

        $this->bodyClass = 'compact-container';
        $this->addCss('/plugins/anguro/capse/assets/css/rainlab.blog-preview.css');
        $this->addJs('/plugins/anguro/capse/assets/js/post-form.js');

        return $this->asExtension('FormController')->create();
    }

    public function update($recordId = null)
    {
        $this->bodyClass = 'compact-container';
        $this->addCss('/plugins/anguro/capse/assets/css/rainlab.blog-preview.css');
        $this->addJs('/plugins/anguro/capse/assets/js/post-form.js');

        return $this->asExtension('FormController')->update($recordId);
    }



    public function index_onDelete()
    {
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {

            foreach ($checkedIds as $eventoId) {
                if ((!$evento = Evento::find($eventoId)) || !$evento->canEdit($this->user)) {
                    continue;
                }

                $evento->delete();
            }

            Flash::success(Lang::get('anguro.capse::lang.eventos.eliminacion_masiva'));
        }

        return $this->listRefresh();
    }

    /**
     * {@inheritDoc}
     */
    public function listInjectRowClass($record, $definition = null)
    {
        if (!$record->published) {
            return 'safe disabled';
        }
    }

    public function formBeforeCreate($model)
    {
        $model->user_id = $this->user->id;
    }

    public function onRefreshPreview()
    {
        $data = post('Evento');

        $previewHtml = Evento::formatHtml($data['descripcion'], true);

        return [
            'preview' => $previewHtml
        ];
    }

    public function onGetDirecciones(){
        $pathRegiones = __DIR__ . '/../assets/js/bdcut-cl/BDCUT_CL_Regiones.min.json';
        $pathProvincias = __DIR__ . '/../assets/js/bdcut-cl/BDCUT_CL_ProvinciaRegion.min.json';
        $pathComunas = __DIR__ . '/../assets/js/bdcut-cl/BDCUT_CL_ComunaProvincia.min.json';

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

        $usuarios = UserModel::all();
        $i = 0;
        $direcciones = [];

        foreach($usuarios as $u){
            $direcciones[$i]['direccion'] = $u->direccion;
            $direcciones[$i]['region'] = $regiones[$u->region];
            $direcciones[$i]['provincia'] = $provincias[$u->region][$u->provincia];
            $direcciones[$i]['comuna'] = $comunas[$u->provincia][$u->comuna];
        }
        
        $this->vars['direcciones'] = $direcciones;

        return [
            '.direcciones' => $this->makePartial('mapa-direcciones')
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
}
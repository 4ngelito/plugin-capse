<?php namespace Anguro\Capse\Controllers;

use BackendMenu;
use File;
use Response;
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
        $this->addJs('/plugins/anguro/capse/assets/js/evento-form.js');
        $this->addCss('/plugins/anguro/capse/assets/css/google-maps.css');

        $gMapsApiKey = env('GOOGLE_API_KEY');
        $gMapsScript = "https://maps.googleapis.com/maps/api/js?key={$gMapsApiKey}&libraries=places";

        $this->addJs('/plugins/anguro/capse/assets/js/google-maps.js');
        $this->addJs($gMapsScript, ['async' => 'async', 'defer' => 'defer']);        

        return $this->asExtension('FormController')->create();
    }

    public function update($recordId = null)
    {
        $this->bodyClass = 'compact-container';
        $this->addCss('/plugins/anguro/capse/assets/css/rainlab.blog-preview.css');
        $this->addJs('/plugins/anguro/capse/assets/js/evento-form.js');
        $this->addCss('/plugins/anguro/capse/assets/css/google-maps.css');

        $gMapsApiKey = env('GOOGLE_API_KEY');
        $gMapsScript = "https://maps.googleapis.com/maps/api/js?key={$gMapsApiKey}&callback=launchMap";

        $this->addJs('/plugins/anguro/capse/assets/js/google-maps.js');
        $this->addJs($gMapsScript, ['async' => 'async', 'defer' => 'defer']); 

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

    public function getUsuariosGeocodes(){

        $usuarios = UserModel::all();
        $i = 0;
        $geocodes = [];

        foreach($usuarios as $u){
            if($u->geocode){
                $geocodes[$i]['geocode'] = $u->geocode;
                $geocodes[$i]['titulo'] = $u->direccion;
                $i++;
            }
        }
        $response = [
            'n' => $i,
            'geocodes' => $geocodes
        ];
        
        return Response::json($response);
    }
}
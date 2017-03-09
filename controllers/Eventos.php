<?php namespace Anguro\Capse\Controllers;

use BackendMenu;
use Response;
use Rainlab\User\Models\User as UserModel;
use Backend\Classes\Controller;
use Anguro\Capse\Models\Evento;
use System\Classes\SettingsManager;
use Anguro\Capse\Models\Setting;

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
    
    public $requiredPermissions = ['anguro.capse.access_settings','anguro.capse.access_eventos'];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Anguro.Capse', 'eventos');
        SettingsManager::setContext('Anguro.Capse', 'anguro_capse');
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

        $gMapsApiKey = Setting::get('google_maps_key');
        $gMapsScript = "https://maps.googleapis.com/maps/api/js?key={$gMapsApiKey}&libraries=places";

        $this->addJs('/plugins/anguro/capse/assets/js/google-maps.js');
        //$this->addJs($gMapsScript, ['async' => 'async', 'defer' => 'defer']);
        $this->addJs($gMapsScript);

        return $this->asExtension('FormController')->create();
    }

    public function update($recordId = null)
    {
        $evento = Evento::find($recordId);
        $this->vars['eventoLocation'] = "{lat: {$evento->geocode['location']['lat']}, lng:{$evento->geocode['location']['lng']}}";
        
        $this->bodyClass = 'compact-container';
        $this->addCss('/plugins/anguro/capse/assets/css/rainlab.blog-preview.css');
        $this->addJs('/plugins/anguro/capse/assets/js/evento-form.js');
        $this->addCss('/plugins/anguro/capse/assets/css/google-maps.css');

        $gMapsApiKey = Setting::get('google_maps_key');
        $gMapsScript = "https://maps.googleapis.com/maps/api/js?key={$gMapsApiKey}&libraries=places";

        $this->addJs('/plugins/anguro/capse/assets/js/google-maps.js');
//        $this->addJs($gMapsScript, ['async' => 'async', 'defer' => 'defer']); 
        $this->addJs($gMapsScript);

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
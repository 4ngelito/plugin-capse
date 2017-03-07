<?php namespace Anguro\Capse\Components;

use Cms\Classes\ComponentBase;
use Anguro\Capse\Models\Patrocinador as CapsePatrocinador;

class Patrocinador extends ComponentBase
{
    /**
     * A collection of eventos to display
     * @var Collection
     */
    public $patrocinadores;
    
    /**
     * Message to display when there are no messages.
     * @var string
     */
    public $noPatrocinadoresMessage;
    
    public function componentDetails()
    {
        return [
            'name'        => 'anguro.capse::lang.patrocinador.patrocinador',
            'description' => 'anguro.capse::lang.patrocinador.description',
        ];
    }

    public function defineProperties()
    {
        return [
            'noPatrocinadoresMessage' => [
                'title'        => 'anguro.capse::lang.settings.patrocinador_no_patrocinador',
                'description'  => 'anguro.capse::lang.settings.patrocinador_no_patrocinador_description',
                'type'         => 'string',
                'default'      => 'Ningun socio encontrado',
                'showExternalParam' => false
            ]
        ];
    }
    
    public function onRun(){
        
        $this->patrocinadores = $this->page['patrocinadores'] = $this->listPatrocinadores();
        $this->noEventosMessage = $this->page['noPatrocinadoresMessage'] = $this->property('noPatrocinadoresMessage');
        
        //$this->addCss('/plugins/anguro/capse/assets/css/patrocinadores.css');
    }
    
    protected function listPatrocinadores()
    {
        
        $patrocinadores = CapsePatrocinador::all();
                
        return $patrocinadores;
    }
}

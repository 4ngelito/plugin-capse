<?php namespace Anguro\Capse\Components;

use Cms\Classes\ComponentBase;
use Anguro\Capse\Models\Socio as CapseSocios;

class Socios extends ComponentBase
{
    /**
     * A collection of eventos to display
     * @var Collection
     */
    public $socios;
    
    /**
     * Message to display when there are no messages.
     * @var string
     */
    public $noSociosMessage;
    
    public function componentDetails()
    {
        return [
            'name'        => 'anguro.capse::lang.socio.socio',
            'description' => 'anguro.capse::lang.socio.description',
        ];
    }

    public function defineProperties()
    {
        return [
            'noSociosMessage' => [
                'title'        => 'anguro.capse::lang.settings.socio_no_socio',
                'description'  => 'anguro.capse::lang.settings.socio_no_socio_description',
                'type'         => 'string',
                'default'      => 'Ningun socio encontrado',
                'showExternalParam' => false
            ]
        ];
    }
    
    public function onRun(){
        
        $this->socios = $this->page['socios'] = $this->listSocios();
        $this->noSociosMessage = $this->page['noSociosMessage'] = $this->property('noSociosMessage');
        
        //$this->addCss('/plugins/anguro/capse/assets/css/socios.css');
    }
    
    protected function listSocios()
    {
        
        $socios = CapseSocios::all();
                
        return $socios;
    }
}

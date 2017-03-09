<?php namespace Anguro\Capse\Components;

use Cms\Classes\ComponentBase;
use Anguro\Capse\Models\Faq as CapseFaq;

class Preguntas extends ComponentBase
{
    /**
     * A collection of preguntas to display
     * @var Collection
     */
    public $preguntas;
    
    public function componentDetails()
    {
        return [
            'name'        => 'anguro.capse::lang.faqs.faqs',
            'description' => 'anguro.capse::lang.faqs.descripcion'
        ];
    }

    public function defineProperties()
    {
        return [];
    }
    
    public function onRun()
    {
        $this->preguntas = $this->page['faqs'] = $this->listFaqs();
    }
    
    private function listFaqs(){
        $preguntas = CapseFaq::all();
        return $preguntas;
    }
}

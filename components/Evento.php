<?php namespace Anguro\Capse\Components;

use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Anguro\Capse\Models\Evento as CapseEvento;
use Anguro\Capse\Models\Setting;

class Evento extends ComponentBase
{
    /**
     * @var Anguro\Capse\Models\Evento The evento model used for display.
     */
    public $evento;

    public function componentDetails()
    {
        return [
            'name'        => 'anguro.capse::lang.evento.evento',
            'description' => 'anguro.capse::lang.evento.descripcion'
        ];
    }

    public function defineProperties()
    {
        return [
            'slug' => [
                'title'       => 'anguro.capse::lang.settings.evento_slug',
                'description' => 'anguro.capse::lang.settings.evento_slug_description',
                'default'     => '{{ :slug }}',
                'type'        => 'string'
            ],
        ];
    }
    
    public function onRun()
    {
        $this->evento = $this->page['evento'] = $this->loadEvento();
        $this->page->title = $this->evento->titulo;
        
        $this->prepareVars();
        
        $this->addCss('/plugins/anguro/capse/assets/css/google-maps.css');
        $this->addJs('/plugins/anguro/capse/assets/js/evento.js');
        
    }
    
    protected function prepareVars(){
        $this->page['direccion'] = $this->evento->direccion;
        $this->page['fecha'] = $this->evento->cuando->toFormattedDateString();
        $this->page['hora'] = $this->evento->cuando->format('H:i');
        $this->page['featuredImagePath'] = $this->evento->getFeaturedImagePath();
        $this->page['google_maps_key'] = Setting::get('google_maps_key');
    }

    protected function loadEvento()
    {
        $slug = $this->property('slug');

        $evento = new CapseEvento;

        $evento = $evento->isClassExtendedWith('RainLab.Translate.Behaviors.TranslatableModel')
            ? $evento->transWhere('slug', $slug)
            : $evento->where('slug', $slug);

        $evento = $evento->first();

        /*
         * Add a "url" helper attribute for linking to each category
        
        if ($evento && $evento->categories->count()) {
            $evento->categories->each(function($category){
                $category->setUrl($this->categoryPage, $this->controller);
            });
        }
        */
        return $evento;
    }

}
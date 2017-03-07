<?php namespace Anguro\Capse\Components;

use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Anguro\Capse\Models\Evento as CapseEvento;

class Eventos extends ComponentBase
{
    /**
     * A collection of eventos to display
     * @var Collection
     */
    public $eventos;

    /**
     * Parameter to use for the page number
     * @var string
     */
    public $pageParam;

    /**
     * Message to display when there are no messages.
     * @var string
     */
    public $noEventosMessage;

    /**
     * Reference to the page name for linking to posts.
     * @var string
     */
    public $eventoPage;

    /**
     * If the post list should be ordered by another attribute.
     * @var string
     */
    public $sortOrder;
    
    public function componentDetails()
    {
        return [
            'name'        => 'anguro.capse::lang.eventos.eventos',
            'description' => 'anguro.capse::lang.eventos.descripcion'
        ];
    }

    public function defineProperties()
    {
        return [
            'pageNumber' => [
                'title'       => 'anguro.capse::lang.settings.eventos_pagination',
                'description' => 'anguro.capse::lang.settings.eventos_pagination_description',
                'type'        => 'string',
                'default'     => '{{ :page }}',
            ],
            'eventosPerPage' => [
                'title'             => 'anguro.capse::lang.settings.eventos_per_page',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'anguro.capse::lang.settings.eventos_per_page_validation',
                'default'           => '10',
            ],
            'noEventosMessage' => [
                'title'        => 'anguro.capse::lang.settings.eventos_no_eventos',
                'description'  => 'anguro.capse::lang.settings.eventos_no_eventos_description',
                'type'         => 'string',
                'default'      => 'Ningun evento encontrado',
                'showExternalParam' => false
            ],
            'sortOrder' => [
                'title'       => 'anguro.capse::lang.settings.eventos_order',
                'description' => 'anguro.capse::lang.settings.eventos_order_description',
                'type'        => 'dropdown',
                'default'     => 'cuando desc'
            ],
            'eventoPage' => [
                'title'       => 'anguro.capse::lang.settings.eventos_post',
                'description' => 'anguro.capse::lang.settings.eventos_post_description',
                'type'        => 'dropdown',
                'default'     => 'capse/evento',
                'group'       => 'Links',
            ]
        ];
    }
    
    public function getEventoPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }
    
    public function getSortOrderOptions()
    {
        return CapseEvento::$allowedSortingOptions;
    }
    
    public function onRun()
    {
        $this->prepareVars();
        $this->eventos = $this->page['eventos'] = $this->listEventos();
        
        $this->addCss('/plugins/anguro/capse/assets/css/eventos.css');

        /*
         * If the page number is not valid, redirect
         */
        if ($pageNumberParam = $this->paramName('pageNumber')) {
            $currentPage = $this->property('pageNumber');

            if ($currentPage > ($lastPage = $this->eventos->lastPage()) && $currentPage > 1)
                return Redirect::to($this->currentPageUrl([$pageNumberParam => $lastPage]));
        }
    }

    protected function prepareVars()
    {
        $this->pageParam = $this->page['pageParam'] = $this->paramName('pageNumber');
        $this->noEventosMessage = $this->page['noEventosMessage'] = $this->property('noEventosMessage');

        /*
         * Page links
         */
        $this->eventoPage = $this->page['eventoPage'] = $this->property('eventoPage');
    }
    
    protected function listEventos()
    {
        
        $eventos = CapseEvento::listFrontEnd([
            'page'       => $this->property('pageNumber'),
            'sort'       => $this->property('sortOrder'),
            'perPage'    => $this->property('eventosPerPage'),
            'search'     => trim(input('search')),
        ]);
        
        /*
         * Add a "url" helper attribute for linking to each post and category
         */
        $eventos->each(function($evento) {
            $evento->setUrl($this->eventoPage, $this->controller);
            $evento->setMapUrl();
        });
                
        return $eventos;
    }
}

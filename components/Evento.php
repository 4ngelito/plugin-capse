<?php namespace Anguro\Capse\Components;

use Cms\Classes\ComponentBase;

class Evento extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'anguro.capse::lang.evento.evento',
            'description' => 'anguro.capse::lang.evento.descripcion'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

}
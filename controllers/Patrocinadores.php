<?php namespace Anguro\Capse\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Anguro\Capse\Models\Patrocinador;

/**
 * Patrocinadores Back-end Controller
 */
class Patrocinadores extends Controller
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

        BackendMenu::setContext('Anguro.Capse', 'capse', 'patrocinadores');
    }
}

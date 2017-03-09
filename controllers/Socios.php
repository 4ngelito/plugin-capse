<?php namespace Anguro\Capse\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Anguro\Capse\Models\Socio;

/**
 * Socios Back-end Controller
 */
class Socios extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    
    public $requiredPermissions = ['anguro.capse.access_socios'];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Anguro.Capse', 'socios');
    }
}

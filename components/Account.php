<?php namespace Anguro\Capse\Components;

use Lang;
use Auth;
use File;
use RainLab\User\Models\User as UserModel;
use RainLab\User\Controllers\Users as UsersController;
use RainLab\User\Components\Account as UserAccountComponent;
use Exception;

class Account extends UserAccountComponent
{
    public function componentDetails()
    {
        return [
            'name'        => 'anguro.capse::lang.account.account',
            'description' => 'rainlab.user::lang.account.account_desc'
        ];
    }

    /**
     * Executed before AJAX handlers and before the page execution life cycle.
     */
    public function init()
    {
        $this->page['provincias'] = $this->getProvincias(post('region'));
        $this->page['comunas'] = $this->getComunas(post('provincia'));
        $this->addComponent('RainLab\User\Components\Account', '_account', []);
    }

    /**
     * Executed when this component is bound to a page or layout.
     */
    public function onRun()
    {   
        $u = Auth::getUser();
        $region = isset($u->region) ? $u->region : null;
        $provincia = isset($u->provincia) ? $u->provincia : null;
        $this->page['__PARENT__'] = '_account';
        $this->page['__PREFIX__'] = 'cuidador_';
        $this->page['regiones'] = $this->getRegiones();
        $this->page['provincias'] = $this->getProvincias($region);
        $this->page['comunas'] = $this->getComunas($provincia);
        
        $this->addJs('/plugins/anguro/capse/assets/js/capse.js');
        $this->addCss('/plugins/anguro/capse/assets/css/capse.css');
        
        return parent::onRun();
    }
    
    public function onRegister() {
        $rules['name'] = '';
        $rules['surname'] = '';
        $rules['rut'];
        $rules['fecha_nacimiento'];
        $rules['sexo'];
        
        parent::onRegister();
    }
    
    public function onCheckEmail(){
        return ['isTaken' => Auth::findUserByLogin(post('email')) ? 1 : 0];
    }
    
    private function getRegiones(){
        $jsonFile = __DIR__ . '/../assets/js/bdcut-cl/BDCUT_CL_Regiones.min.json';
        
        $json = json_decode(File::get($jsonFile), false, 512, JSON_UNESCAPED_UNICODE);
        
        $regiones = null;
        foreach ($json as $region) {
            $regiones[$region->region_id] = $region->name;
        }
        
        return $regiones;
    }
    
    private function getProvincias($region = NULL){
        if($region == NULL)
            return [ '' => '-- Seleccione Regi&oacute;n --'];
        
        $jsonFile = __DIR__ . '/../assets/js/bdcut-cl/BDCUT_CL_ProvinciaRegion.min.json';
        $json = json_decode(File::get($jsonFile), false, 512, JSON_UNESCAPED_UNICODE);
        $provincias = null;
        
        foreach ($json as $region_id => $p) {
            if($region_id == $region){
                foreach ($p as $prov) {
                    $provincias[$prov->provincia_id] = $prov->name;
                }
            }
        }
        
        return $provincias;
    }
    
    private function getComunas($provincia = NULL){
        if($provincia == NULL)
            return [ '' => '-- Seleccione Provincia --'];
        
        $jsonFile = __DIR__ . '/../assets/js/bdcut-cl/BDCUT_CL_ComunaProvincia.min.json';
        $json = json_decode(File::get($jsonFile), false, 512, JSON_UNESCAPED_UNICODE);
        $comunas = null;
        
        foreach ($json as $provincia_id => $c) {
            if($provincia_id == $provincia){
                foreach ($c as $com) {
                    $comunas[$com->comuna_id] = $com->name;
                }
            }
        }
        
        return $comunas;
    }
}


<?php namespace Anguro\Capse\Components;

use Lang;
use Auth;
use File;
use Input;
use Request;
use Validator;
use ValidationException;
use Flash;
use RainLab\User\Models\User as UserModel;
use RainLab\User\Controllers\Users as UsersController;
use RainLab\User\Components\Account as UserAccountComponent;
use Exception;

class Account extends UserAccountComponent
{
    public function componentDetails()
    {
        return [
            'name'        => 'anguro.capse::lang.cuenta.cuenta',
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
        $this->addCss('/plugins/anguro/capse/assets/css/videosyoutube.css');
        
        return parent::onRun();
    }

    /**
     * Override Register the user
     */
    public function onRegister()
    {
        try {
            if (!UserSettings::get('allow_registration', true)) {
                throw new ApplicationException(Lang::get('rainlab.user::lang.account.registration_disabled'));
            }

            /*
             * Validate input
             */
            $data = post();

            if (!array_key_exists('password_confirmation', $data)) {
                $data['password_confirmation'] = post('password');
            }

            $rules = [
                'email'    => 'required|email|between:6,255',
                'password' => 'required|between:4,255'
            ];
            $rules['name'] = 'required|min:3';
            $rules['surname'] = 'required|min:3';
            $rules['rut'] = ['required',
                'min:9',
                'regex:\b\d{7,9}\-[K|0-9]'];
            $rules['fecha_nacimiento'] = 'required|date';
            $rules['sexo'] = 'required';

            if ($this->loginAttribute() == UserSettings::LOGIN_USERNAME) {
                $rules['username'] = 'required|between:2,255';
            }

            $validation = Validator::make($data, $rules);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            /*
             * Register user
             */
            $requireActivation = UserSettings::get('require_activation', true);
            $automaticActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_AUTO;
            $userActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_USER;
            $user = Auth::register($data, $automaticActivation);

            /*
             * Activation is by the user, send the email
             */
            if ($userActivation) {
                $this->sendActivationEmail($user);

                Flash::success(Lang::get('rainlab.user::lang.account.activation_email_sent'));
            }

            /*
             * Automatically activated or not required, log the user in
             */
            if ($automaticActivation || !$requireActivation) {
                Auth::login($user);
            }

            /*
             * Redirect to the intended page after successful sign in
             */
            $redirectUrl = $this->pageUrl($this->property('redirect'))
                ?: $this->property('redirect');

            if ($redirectUrl = post('redirect', $redirectUrl)) {
                return Redirect::intended($redirectUrl);
            }

        }
        catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }
    
    public function onCheckEmail(){
        return ['isTaken' => Auth::findUserByLogin(post('email')) ? 1 : 0];
    }

    public function onDummy(){}

    public function onAvatarUpdate(){
        if (!$user = $this->user()) {
            return;
        } 

        $res['response'] = 'error';
        $res['message'] = 'error indeterminado!';

        try {
            
            $archivo = Input::file('avatar_file');
            $ext = $archivo->getMimeType();
            $extension = ['image/jpeg','image/png'];

            //comprueba que sea un formato de imagen
            if(!in_array($ext, $extension)){
                throw new ValidationException(['avatar_file' => Lang::get('anguro.capse::lang.messages.imagen_invalida')]);
            }

            if($avatar = $this->user()->avatar){
                $avatar->delete();
            }

            $file = new \System\Models\File;
            $file->fromPost($archivo);

            $user->avatar()->add($file);
            $user->save();

            $res['response'] = 'success';
            $res['imagen'] = [
                'avatar' => $user->getAvatarThumb(250),
                'titulo' => $user->name,
                'descripcion' => 'avatar de ' . $user->name
                ];
            Flash::success('Imagen Actualizada correctamente!');
        }
        catch (Exception $ex) {
            Flash::error($ex->getMessage());
        }
        return ['result'=> $res];   
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


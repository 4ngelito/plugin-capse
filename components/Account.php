<?php namespace Anguro\Capse\Components;

use Lang;
use Auth;
use File;
use Input;
use Request;
use Redirect;
use Validator;
use ValidationException;
use Flash;
use RainLab\User\Models\Settings as UserSettings;
use RainLab\User\Components\Account as UserAccountComponent;
use Exception;
use GuzzleHttp\Client as GuzzleClient;

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
            $rules['name'] = 'required|between:3,255';
            $rules['surname'] = 'required|between:3,255';
            $rules['rut'] = ['required',
                'regex:/^\b\d{7,9}\-[K|0-9]{1}$/'
                ];
            $rules['fecha_nacimiento'] = 'required|date';
            $rules['sexo'] = 'required';

            if ($this->loginAttribute() == UserSettings::LOGIN_USERNAME) {
                $rules['username'] = 'required|between:2,255';
            }

            $validation = Validator::make($data, $rules);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }
            
            if($data['password'] !== $data['password_confirmation']){
                throw new ValidationException(Lang::get('anguro.capse::lang.messages.password_missmatch'));
            }

            /*
             * Register user
             */
            $requireActivation = UserSettings::get('require_activation', true);
            $automaticActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_AUTO;
            $userActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_USER;
            $user = Auth::register($data, $automaticActivation);
            
            $user->addUserGroup('cuidadores');

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
    
    /**
     * Override Update the user
     */
    public function onUpdate()
    {
        if (!$user = $this->user()) {
            return;
        }
        
        $rules = ['email' => 'required|email|between:6,255'];
        $rules['name'] = 'required|between:3,255';
        $rules['surname'] = 'required|between:3,255';
        $rules['rut'] = [
            'required',
            'regex:/^\b\d{7,9}\-[K|0-9]{1}$/'
        ];
        $rules['fecha_nacimiento'] = 'required|date';
        $rules['sexo'] = 'required';
        $rules['direccion'] = 'string|between:3,255';
        
        if(strlen(post('password'))){
            $rules['password'] = 'confirmed|between:4,255';
        }
        
        $t = [];
        $telefonos = post('telefonos');
        $n = count($telefonos['tipo']);
        for($i = 0; $i < $n; $i++){
            $t[$i]['tipo'] = $telefonos['tipo'][$i];
            $t[$i]['numero'] = $telefonos['numero'][$i];
            
            //valida numero de telefono
            $v = Validator::make($t[$i],['numero' => 'digits_between:8,9']);
            if ($v->fails()) {
                throw new ValidationException($v);
            }
        }
        
        $p = [];
        $pacientes = post('pacientes');
        $n = count($pacientes['parentesco']);
        for($i = 0; $i < $n; $i++){
            $p[$i]['parentesco'] = $pacientes['parentesco'][$i];
            $p[$i]['sexo'] = $pacientes['sexo'][$i];
            //valida existencia del sexo del paciente
            $v = Validator::make($p[$i],['sexo' => 'required']);
            if ($v->fails()) {
                throw new ValidationException($v);
            }
        }
        
        $validation = Validator::make(post(), $rules);
        $validation->sometimes(['region', 'provincia', 'comuna'], 'required', function($input){
            return strlen($input->direccion) > 1;
        });
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $this->validaRut(post('rut'));

        $dirAnterior = $user->direccion;
        $geocode = $user->geocode;

        $user->fill(post());

        $user->telefonos = $t;
        $user->pacientes = $p;

        if(strlen(post('direccion')) && $dirAnterior !== post('direccion')){
            $dir = urlencode($user->getDireccionCompleta());
        }
        if(isset($dir)){
            $geoResponse = $this->getDireccionGeocode($dir);
            if($geoResponse->status === 'OK'){
                $res = $geoResponse->results[0];
                $geocode = [
                    'location' => [
                        'lat' => $res->geometry->location->lat,
                        'lng' => $res->geometry->location->lng
                    ],
                    'place_id' => $res->place_id
                ];
            }
        }

        $user->geocode = $geocode;
        $user->save();

        /*
         * Password has changed, reauthenticate the user
         */
        if (strlen(post('password'))) {
            Auth::login($user->reload(), true);
        }

        Flash::success(post('flash', Lang::get('anguro.capse::lang.cuenta.success_saved')));

        /*
         * Redirect
         */
        if ($redirect = $this->makeRedirection()) {
            return $redirect;
        }
    }
    
    /**
     * Verifica si existe el correo 
     * @return array : respuesta Json
     */
    public function onCheckEmail(){
        $r['response'] = 'success';
        $email = post('email');
        
        $emailUsuario = $this->user() ? $this->user()->email : null;
        
        if($email !== $emailUsuario && Auth::findUserByLogin($email)){
            Flash::error(Lang::get('anguro.capse::lang.messages.email_usado'));
            $r['response'] = 'error';
        }
        return $r;
    }

    public function onDummy(){}
    
    /**
     * Actualiza el avatar del usuario
     * @return array : Respuesta json
     * @throws ValidationException
     */
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
                'avatar' => $user->getAvatarThumb(250,['mode' => 'crop']),
                'titulo' => $user->name .' '. $user->surname,
                'descripcion' => 'avatar de ' . $user->name
                ];
            Flash::success('Imagen Actualizada correctamente!');
        }
        catch (Exception $ex) {
            Flash::error($ex->getMessage());
        }
        return ['result' => $res];   
    }

    private function validaRut(string $rut){
        try {
            if(strpos($rut, '-') !== false){
                $multiplicador = [2,3,4,5,6,7];
                $n = 5;
                $rutArray = explode('-', $rut);
                $dv = (string) $rutArray[1];
                $r = array_map('intval', str_split($rutArray[0]));

                $sum = 0;
                $k = 0;
                for ($i = count($r) - 1 ; $i >= 0; $i--){
                    $sum = ($r[$i] * $multiplicador[$k]) + $sum;
                    if($k >= $n){
                        $k = 0;
                    }
                    else $k++;
                }

                $resto = $sum % 11;
                $dvCalculado = (11 - $resto) > 9 ? 'K' : (string) (11 - $resto);
                
                if($dvCalculado !== $dv){
                    throw new ValidationException(['rut' => 'Rut inválido, revise el Dígito verificador']);
                }

                return true;
            }
            else throw new ValidationException(['rut' => 'Rut inválido']);
        }        
        catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }
    
    /**
     * Obtiene las regiones de Chile
     * @return array : Arreglo con todas las regiones contenidas en el archivo
     */
    private function getRegiones(){
        $jsonFile = __DIR__ . '/../assets/js/bdcut-cl/BDCUT_CL_Regiones.min.json';
        
        $json = json_decode(File::get($jsonFile), false, 512, JSON_UNESCAPED_UNICODE);
        
        $regiones = null;
        foreach ($json as $region) {
            $regiones[$region->region_id] = $region->name;
        }
        
        return $regiones;
    }
    
    /**
     * Obtiene las provincias de una region
     * @param string $region : Region a consultar las provincias
     * @return array : Arreglo con todas las provincias de la region contenidas en el archivo
     */
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
    
    /**
     * Obtinee las comunas de una provincia
     * @param string $provincia
     * @return array : Arreglco con las comunas de la provincia contenidas en el archivo
     */
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


    public function getDireccionGeocode(string $direccion){
        $r = null;
        $apiKey = env('GOOGLE_API_KEY');
        $client = new GuzzleClient();
        $res = $client->get("https://maps.googleapis.com/maps/api/geocode/json?address={$direccion}&key={$apiKey}");
        if($res->getStatusCode() == 200){
            $r = $res->getBody();
        }
        return json_decode($r);
    }
}


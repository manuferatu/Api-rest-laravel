<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;

class UserController extends Controller {

    public function pruebas(Request $request) {
        return "Acción de pruebas de UserController";
    }

    public function register(Request $request) {
        /*
          $name = $request->input('name');
          $surname = $request->input('surname');
          return "Acción de registro de usuarios: $name $surname";
         */

        //Recoger los datos del usuario por post, en el caso de que no llegue que sea nulo
        $json = $request->input('json', null);

        //decodificamos el json
        $params = json_decode($json); //objeto
        $params_array = json_decode($json, true); //array
        //var_dump($params_array); die();
        //
        if (!empty($params) && !empty($params_array)) {
            //Limpiar los datos(espacios)
            $params_array = array_map('trim', $params_array);
            //Validar datos
            $validate = \Validator::make($params_array, [
                        'name' => 'required|alpha',
                        'surname' => 'required|alpha',
                        'email' => 'required|email|unique:users', //comprobar si el usuario existe ya (duplicado)
                        'password' => 'required'
            ]);

            if ($validate->fails()) {
                //Validación fallida
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'messge' => 'El usuario no se ha creado',
                    'errors' => $validate->errors()
                );
            } else {
                //Validación pasada correctamente
                //cifrar la contraseña
                //$pwd = password_hash($params->password, PASSWORD_BCRYPT, ['cost' => 4]);
                $pwd = hash('sha256', $params->password);
                //crear el usuario
                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';

                //var_dump($user); die();

                $user->save();

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'messge' => 'El usuario se ha creado correctamente',
                    'user' => $user
                );
            }

            //Creamos array para devolver los datos en json
            return response()->json($data, $data['code']);
        } else {
            $data = array(
                'status' => 'success',
                'code' => 400,
                'messge' => 'Los datos enviados no son correctos'
            );
        }
    }

    public function login(Request $request) {

        $jwtAuth = new \JwtAuth();

        // Recibir datos por POST
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true); // para hacer la validación
        // Validar esos datos
        $validate = \Validator::make($params_array, [
                    'email' => 'required|email', //comprobar si el usuario existe ya (duplicado)
                    'password' => 'required'
        ]);

        if ($validate->fails()) {
            //Validación fallida
            $signup = array(
                'status' => 'error',
                'code' => 404,
                'messge' => 'El usuario no se ha podido identificar',
                'errors' => $validate->errors()
            );
        } else {
            // cifrar la password
            $pwd = hash('sha256', $params->password);
            // Devolver token o datos
            $signup = $jwtAuth->signup($params->email, $pwd);

            if (!empty($params->gettoken)) {
                $signup = $jwtAuth->signup($params->email, $pwd, true);
            }
        }


        //var_dump($pwd); die();
        //return $jwtAuth->signup($email, $pwd);
        return response()->json($signup, 200);
    }

    public function update(Request $request) {

        //comprobar si el usuario esta identificado
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);
        //Recoger los datos por POST
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        //var_dump($params_array); die();
        if ($checkToken && !empty($params_array)) {
            //Actualizar el usuario
            //Sacar usuario identificado
            $user = $jwtAuth->checkToken($token, true);
            //var_dump($user); die();
            //Validar los datos
            $validate = \Validator::make($params_array, [
                        'name' => 'required|alpha',
                        'surname' => 'required|alpha',
                        'email' => 'required|email|unique:users' . $user->sub //comprobar si el usuario existe ya (duplicado)
            ]);
            //Quitar los datos que no quiero actualizar
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['created_up']);
            unset($params_array['remenber_token']);
            //Actualizar la DB
            $user_update = User::where('id', $user->sub)->update($params_array);
            //Devolver array con el resultado
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user,
                'changes' => $params_array
            );
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'El usuario no esta identificado'
            );
        }

        return response()->json($data, $data['code']);
    }

    public function upload(Request $request) {
        //Recoger datos de la petición
        $image = $request->file('file0');

        //Validar la imagen
        $validate = \Validator::make($request->all(), [
                    'file0' => 'required'
        ]);

        //Guardar imagen
        if (!$image || $validate->fails()) {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir la imagen'
            );
        } else {
            $image_name = time() . $image->getClientOriginalName();
            \Storage::disk('users')->put($image_name, \File::get($image));

            $data = array(
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            );
        }

        return response()->json($data, $data['code']);
    }

    public function getImage($filename) {
        $isset = \Storage::disk('users')->exists($filename);
        if ($isset) {
            $file = \Storage::disk('users')->get($filename);
            return new Response($file, 200);
        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'La imagen no existe.'
            );
            return response()->json($data, $data['code']);
        }
    }

    public function detail($id) {
        $user = User::find($id);

        if (is_object($user)) {
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user
            );
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'El usuario no existe'
            );
        }
        return response()->json($data, $data['code']);
    }

}

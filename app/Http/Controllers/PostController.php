<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Post;
use App\Helpers\JwtAuth;

class PostController extends Controller {

    //Creo un contructor para usar el middleware de auentificación en todos los metodos, exepto donde no lo necesito
    public function __construct() {
        $this->middleware('api.auth', ['except' => ['index',
                'show',
                'getImage',
                'getPostsByCategory',
                'getPostsByUser'
        ]]);
    }

    //creamos una funcion para identificar al usuario
    private function getIdentity($request) {
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true); //true para que devuelva array del objeto

        return $user;
    }

    public function index() {
        $posts = Post::all()->load('category');

        return response()->json([
                    'code' => '200',
                    'status' => 'success',
                    'posts' => $posts
                        ], 200);
    }

    public function show($id) {
        $post = Post::find($id)->load('category')
                               ->load('user');

        if (is_object($post)) {
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post
            ];
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'La entrada no existe'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function store(Request $request) {
        //Recoger datos por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            //Conseguir usuario identificado
            //Conseguir usuario identificado
            $user = $this->getIdentity($request);
            //Validar los datos
            //var_dump($params_array); die();
            $validate = \Validator::make($params_array, [
                        'title' => 'required',
                        'content' => 'required',
                        'category_id' => 'required',
                        'image' => 'required'
            ]);
            if ($validate->fails()) {
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No se ha guardado el post, faltan datos'
                ];
            } else {
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;
                $post->save();

                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'post' => $post
                ];
            }
            //Guardar el articulo
        } else {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Envia los datos correctamente'
            ];
        }
        //Devolver los datos
        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request) {
        //Recoger los datos por post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        //Datos a devolver
        $data = array(
            'code' => 400,
            'status' => 'error',
            'message' => 'Datos enviados incorrectamente o no tiene permisos para actualizar'
        );
        if (!empty($params_array)) {


            //validar los datos
            $validate = \Validator::make($params_array, [
                        'title' => 'required',
                        'content' => 'required',
                        'category_id' => 'required'
            ]);
            if ($validate->fails()) {
                $data['status'] = $validate->errors();
                return response()->json($data, $data['code']);
            }
            //Eliminar lo que no queremos actualizar
            unset($params_array['id']);
            unset($params_array['user_id']);
            unset($params_array['created_at']);
            unset($params_array['user']);

            //Conseguir usuario identificado
            $user = $this->getIdentity($request);

            //Buscar el registro
            $post = Post::where('id', $id)
                    ->where('user_id', $user->sub)
                    ->first();
            if (!empty($post) && is_object($post)) {
                //Actualizar el registro en concreto
                $post->update($params_array);
                //Devolver datos
                $data = array(
                    'code' => 200,
                    'status' => 'success',
                    'post' => $post,
                    'changes' => $params_array
                );
            }


            /* //cuando necesitamos crear varias condiciones, creamos un array
              $where = [
              'id' => $id,
              'user_id' => $user->sub
              ];

              $post = Post::updateOrCreate($where, $params_array); //con updateOrCreate obtengo todo el objeto
             */
        }
        return response()->json($data, $data['code']);
    }

    public function destroy($id, Request $request) {

        //Conseguir usuario identificado
        $user = $this->getIdentity($request);
        //conseguir el registro
        $post = Post::where('id', $id)
                ->where('user_id', $user->sub)
                ->first(); //first para mostar el objeto

        if (!empty($post)) {
            //Borrarlo
            $post->delete();
            //Devolver algo
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post
            ];
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'Registro no encontrado'
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function upload(Request $request) {
        //recoger la imagen de la peticion
        $image = $request->file('file0');

        //Validar la imagen
        $validate = \Validator::make($request->all(), [
                    'file0' => 'required'
        ]);
        //Guardar la imagen en disco
        if (!$image || $validate->fails()) {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir la imagen'
            ];
        } else {
            $image_name = time() . $image->getClientOriginalName();
            \Storage::disk("images")->put($image_name, \File::get($image));

            $data = [
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            ];
        }
        //Devolver datos
        return response()->json($data, $data['code']);
    }

    public function getImage($filename) {
        //Comprobar si existe el fichero
        $isset = \Storage::disk('images')->exists($filename);
        //conseguir la imagen
        if ($isset) {
            $file = \Storage::disk('images')->get($filename);

            //Devolver la imagen
            return new Response($file, 200);
        } else {
            //Mostrar el posible error
            $data = [
                'cade' => 404,
                'status' => 'error',
                'message' => 'La imagen no existe'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function getPostsByCategory($id) {
        //Obtener todos los post por categoria
        $posts = Post::where('category_id', $id)->get();

        return response()->json([
                    'status' => 'success',
                    'posts' => $posts
                        ], 200);
    }

    public function getPostsByUser($id) {

        $posts = Post::where('user_id', $id)->get();

        return response()->json([
                    'status' => 'success',
                    'posts' => $posts
                        ], 200);
    }

}

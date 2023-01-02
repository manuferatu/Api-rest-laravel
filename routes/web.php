<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Cargando clases
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Middleware\ApiAuthMiddleware;
/*
//RUTAS DE PRUEBA
Route::get('/', function () {
    return '<h1>Bienvenido al Master</h1>';
});

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/prueba/{nombre?}', function ($nombre = null){
    $texto = '<h2>Texto desde una ruta</h2>';
    $texto .= 'Nombre: '.$nombre;
    //return $texto;
    return view('prueba', array(
        'texto' => $texto
    ));
});

Route::get('/animales', 'pruebaController@index');
Route::get('/test-orm', 'pruebaController@testOrm');
*/
//RUTAS DEL API

    /*Metodos http comunes
     *GET: conseguir datos o recursos
     *POST: Guardar datos o recursos o hacer logica desde el formulario
     *PUT: actualizar datos o recursos
     *DELETE: Elimina datos o recursos
     */

    //RUTAS DE PRUEBA
    //Route::get('usuario/pruebas', 'UserController@pruebas');
    //Route::get('entrada/pruebas', 'CategoryController@pruebas');
    //Route::get('categoria/pruebas', 'PostController@pruebas');

    //Rutas del controlador de usuarios
    Route::post('/api/register', 'UserController@register');
    Route::post('/api/login', 'UserController@login');
    Route::put('/api/user/update', 'UserController@update');
    Route::post('/api/user/upload','UserController@upload')->middleware(ApiAuthMiddleware::class);
    Route::get('/api/user/avatar/{filename}', 'UserController@getImage');
    Route::get('/api/user/detail/{id}', 'UserController@detail');

    //Rutas del controlador de categorias
    Route::resource('/api/category', 'CategoryController');

    //Rutas del controlador de entradas
    Route::resource('/api/post', 'PostController');
    Route::post('/api/post/upload','PostController@upload');
    Route::get('/api/post/image/{filename}', 'PostController@getImage');
    Route::get('/api/post/category/{id}', 'PostController@getPostsByCategory');
    Route::get('/api/post/user/{id}', 'PostController@getPostsByUser');

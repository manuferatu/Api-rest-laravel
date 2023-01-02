<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Post;
use App\Category;

class pruebaController extends Controller {

    public function index() {
        $titulo = 'Animales';
        $animales = ['perro', 'gato', 'tigre'];

        return view('prueba.index', array(
            'titulo' => $titulo,
            'animales' => $animales
        ));
    }

    public function testOrm() {
        /*
          $posts = Post::all();
          //var_dump($post);
          foreach ($posts as $post){
          echo '<h1>'.$post->title.'</h1>';
          echo "<spam style='color:gray;'>{$post->user->name} - {$post->category->name}</spam>";
          echo '<p>'.$post->content.'</p>';
          echo '<hr>';
          }
         */
        $categories = Category::all();
        foreach ($categories as $category) {
            echo "<h1>{$category->name}</h1>";
            foreach ($category->posts as $post) {
                echo '<h3>' . $post->title . '</h3>';
                echo "<spam style='color:gray;'>{$post->user->name} - {$post->category->name}</spam>";
                echo '<p>' . $post->content . '</p>';
            }
            
            echo '<hr>';
        }
        die();
    }

}

<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

// ESTA ES LA RUTA DE LIMPIEZA
Route::get('/limpiar', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    return "Servidor refrescado. ¡Prueba ahora!";
});


Route::get('/run-migrations', function () {
    try {
        Artisan::call('migrate', ['--force' => true]);
        return "Migraciones ejecutadas con éxito: " . Artisan::output();
    } catch (\Exception $e) {
        return "Error ejecutando migraciones: " . $e->getMessage();
    }
});
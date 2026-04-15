<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\AuthController;
use App\Models\Semester;
use App\Models\Shift;

// --- RUTAS PÚBLICAS (Visitantes) ---
Route::post('/login', [AuthController::class, 'login']);        // Para que el admin entre
Route::get('/login', function() {
    return response()->json(['message' => 'Método no permitido. Use POST desde Angular.'], 405);
});
Route::get('/projects', [ProjectController::class, 'index']);   // Ver proyectos
Route::get('/semesters', fn() => App\Models\Semester::orderBy('id', 'asc')->get());             // Ver semestres para filtros
Route::get('/shifts', fn() => App\Models\Shift::orderBy('id', 'asc')->get());                    // Ver turnos

// --- RUTAS PROTEGIDAS (Solo Admin con Token) ---
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/projects', [ProjectController::class, 'store']);
    
    Route::match(['post', 'put'], '/projects/{project}', [ProjectController::class, 'update']);
    
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);


    // Gestión de Alumnos y Profes (Solo el Admin puede crearlos)
    Route::apiResource('students', StudentController::class);
    Route::apiResource('teachers', TeacherController::class);
});
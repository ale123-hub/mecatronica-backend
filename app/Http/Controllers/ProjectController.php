<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    private $cloudinary_url = "https://api.cloudinary.com/v1_1/dgdzygi4j/image/upload";
    private $preset = "ml_default"; // Tu preset unsigned de Cloudinary

    public function index()
    {
        try {
            return response()->json(Project::with(['semester', 'shift', 'students', 'teachers'])->get());
        } catch (\Throwable $e) {
            Log::error('Error en Index: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

public function store(Request $request)
    {
        try {
            // 1. Validamos tolerando que las imágenes o categorías sean opcionales
            $validatedData = $request->validate([
                'title'       => 'required|string|max:255',
                'description' => 'nullable|string',
                'category'    => 'nullable|string|max:100',
                'semester_id' => 'required|exists:semesters,id',
                'shift_id'    => 'required|exists:shifts,id',
                'image'       => 'nullable|image|max:10240',
            ]);

            $validatedData['image'] = null;

            // 2. Subida limpia de imagen a Cloudinary leyendo el binario
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                
                $response = Http::attach(
                    'file', 
                    file_get_contents($file->getRealPath()), 
                    $file->getClientOriginalName()
                )->post($this->cloudinary_url, [
                    'upload_preset' => $this->preset
                ]);

                if ($response->successful()) {
                    $validatedData['image'] = $response->json()['secure_url'];
                } else {
                    Log::error('Error Cloudinary (Store): ' . $response->body());
                    return response()->json(['error' => 'Error al subir imagen a Cloudinary'], 400);
                }
            }

            // 3. Crear el proyecto en BD
            $project = Project::create($validatedData);
            
            // 4. Sincronizar relaciones de forma segura (Inmune a datos vacíos)
            $this->syncRelations($project, $request);

            return response()->json($project->load(['semester', 'shift', 'students', 'teachers']), 201);

        } catch (\Throwable $e) {
            Log::error('Error Crítico Store Backend: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error interno en el servidor',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Project $project)
    {
        try {
            $validatedData = $request->validate([
                'title'       => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'category'    => 'nullable|string|max:100',
                'semester_id' => 'sometimes|required|exists:semesters,id',
                'shift_id'    => 'sometimes|required|exists:shifts,id',
                'image'       => 'nullable|image|max:10240',
            ]);

            // Si el front no manda un archivo de imagen nuevo, preservamos la URL previa de la BD
            if (!$request->hasFile('image')) {
                unset($validatedData['image']);
            } else {
                $file = $request->file('image');

                $response = Http::attach(
                    'file', 
                    file_get_contents($file->getRealPath()), 
                    $file->getClientOriginalName()
                )->post($this->cloudinary_url, [
                    'upload_preset' => $this->preset
                ]);

                if ($response->successful()) {
                    $validatedData['image'] = $response->json()['secure_url'];
                } else {
                    Log::error('Error Cloudinary (Update): ' . $response->body());
                    return response()->json(['error' => 'Error al actualizar imagen en Cloudinary'], 400);
                }
            }

            // Actualizar proyecto en BD
            $project->update($validatedData);
            
            // Sincronizar relaciones
            $this->syncRelations($project, $request);

            return response()->json($project->load(['semester', 'shift', 'students', 'teachers']));
        } catch (\Throwable $e) {
            Log::error('Error Crítico Update Backend: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return response()->json(['message' => 'Eliminado'], 200);
    }

    // --- ESTA ES LA FUNCIÓN QUE FALTABA Y QUE CAUSABA EL ERROR 500 ---
    private function syncRelations($project, $request)
    {
        $sRaw = $request->input('student_ids') ?? $request->input('student_ids[]') ?? [];
        $tRaw = $request->input('teacher_ids') ?? $request->input('teacher_ids[]') ?? [];

        $students = is_string($sRaw) ? json_decode($sRaw, true) : (array)$sRaw;
        $teachers = is_string($tRaw) ? json_decode($tRaw, true) : (array)$tRaw;

        $sIds = [];
        foreach ($students as $s) {
            if (!$s) continue;
            if (is_numeric($s)) $sIds[] = (int)$s;
            else $sIds[] = Student::firstOrCreate(['name' => trim($s)])->id;
        }
        $project->students()->sync($sIds);

        $tIds = [];
        foreach ($teachers as $t) {
            if (!$t) continue;
            if (is_numeric($t)) $tIds[] = (int)$t;
            else $tIds[] = Teacher::firstOrCreate(['name' => trim($t)])->id;
        }
        $project->teachers()->sync($tIds);
    }
}
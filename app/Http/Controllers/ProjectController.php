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
    public function index()
    {   
         $projects = Project::with(['semester', 'shift', 'students', 'teachers', 'images'])->get();
        return response()->json($projects);
}
public function store(Request $request)
{
    try {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'nullable|string|max:100',
            'semester_id' => 'required|exists:semesters,id',
            'shift_id'    => 'required|exists:shifts,id',
            'image'       => 'nullable|image|max:10240',
            'extra_images'  => 'nullable|array', // Validamos que venga un grupo
            'extra_images.*' => 'image|max:10240', // Y que cada uno sea imagen
        ]);

        // 1. Subir Imagen Principal a Cloudinary
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $base64 = 'data:image/' . $file->getClientOriginalExtension() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
            
            $response = Http::post("https://api.cloudinary.com/v1_1/dgdzygi4j/image/upload", [
                'file'          => $base64, 
                'upload_preset' => 'preset_mecatronica',
            ]);

            if ($response->successful()) {
                $data['image'] = $response->json()['secure_url'];
            }
        }

        // 2. Crear el proyecto primero
        $project = Project::create($data);
        
        // 3. Subir y guardar las imágenes del carrusel
        if ($request->hasFile('extra_images')) {
            foreach ($request->file('extra_images') as $extraFile) {
                $extraBase64 = 'data:image/' . $extraFile->getClientOriginalExtension() . ';base64,' . base64_encode(file_get_contents($extraFile->getRealPath()));
                
                $extraResponse = Http::post("https://api.cloudinary.com/v1_1/dgdzygi4j/image/upload", [
                    'file' => $extraBase64,
                    'upload_preset' => 'preset_mecatronica',
                ]);

                if ($extraResponse->successful()) {
                    // AQUÍ ESTÁ EL TRUCO: Creamos el registro en la tabla project_images
                    $project->images()->create([
                        'image_url' => $extraResponse->json()['secure_url']
                    ]);
                }
            }
        }

        // Sincronizar relaciones (estudiantes/profesores) si tienes el método
        $this->syncRelations($project, $request);

        return response()->json($project->load(['images', 'semester', 'shift', 'students', 'teachers']), 201);

    } catch (\Throwable $e) {
        return response()->json([
            'error' => 'Error en el servidor',
            'detalle' => $e->getMessage()
        ], 500);
    }
}

    public function update(Request $request, Project $project)
    {
        try {
            $data = $request->validate([
                'title'       => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'category'    => 'nullable|string|max:100',
                'semester_id' => 'sometimes|required|exists:semesters,id',
                'shift_id'    => 'sometimes|required|exists:shifts,id',
                'image'       => 'nullable|image|max:10240',
            ]);

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $response = Http::post("https://api.cloudinary.com/v1_1/dgdzygi4j/image/upload", [
                    'file'          => 'data:image/' . $file->getClientOriginalExtension() . ';base64,' . base64_encode(file_get_contents($file->getRealPath())),
                    'upload_preset' => 'ml_default',
                ]);

                if ($response->successful()) {
                    $data['image'] = $response->json()['secure_url'];
                }
            }

            $project->update($data);
            $this->syncRelations($project, $request);

            return response()->json($project->load(['semester', 'shift', 'students', 'teachers']));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return response()->json(['message' => 'Eliminado'], 200);
    }

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
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
    private $preset = "ml_default";

    public function index()
    {
        return response()->json(Project::with(['semester', 'shift', 'students', 'teachers'])->get());
    }

    public function store(Request $request)
    {
        try {
            // 1. Validamos los datos de entrada
            $validatedData = $request->validate([
                'title'       => 'required|string|max:255',
                'description' => 'nullable|string',
                'category'    => 'nullable|string|max:100',
                'semester_id' => 'required|exists:semesters,id',
                'shift_id'    => 'required|exists:shifts,id',
                'image'       => 'nullable|image|max:10240',
            ]);

            // Inicializamos la URL de la imagen como nula por defecto
            $validatedData['image'] = null;

            // 2. Si el frontend envió un archivo, lo subimos de forma nativa a Cloudinary
            if ($request->hasFile('image')) {
                $file = $request->file('image');

                // Enviamos el archivo real usando attach() en lugar de Base64
                $response = Http::asMultipart()->post($this->cloudinary_url, [
                    'file'          => fopen($file->getRealPath(), 'r'),
                    'upload_preset' => $this->preset,
                ]);

                if ($response->successful()) {
                    $validatedData['image'] = $response->json()['secure_url'];
                } else {
                    Log::error('Error En Cloudinary (Store): ' . $response->body());
                    return response()->json([
                        'error' => 'No se pudo subir la imagen a Cloudinary',
                        'detalle' => $response->json()
                    ], 400);
                }
            }

            // 3. Creamos el registro con la URL de Cloudinary asignada
            $project = Project::create($validatedData);
            
            $this->syncRelations($project, $request);

            return response()->json($project->load(['semester', 'shift', 'students', 'teachers']), 201);

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
            $validatedData = $request->validate([
                'title'       => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'category'    => 'nullable|string|max:100',
                'semester_id' => 'sometimes|required|exists:semesters,id',
                'shift_id'    => 'sometimes|required|exists:shifts,id',
                'image'       => 'nullable|image|max:10240',
            ]);

            // Si el formulario no trae un archivo de imagen nuevo,
            // removemos la llave para preservar la URL que ya existía en la BD
            if (!$request->hasFile('image')) {
                unset($validatedData['image']);
            } else {
                // Si trae un archivo nuevo, lo procesamos y subimos a Cloudinary
                $file = $request->file('image');

                $response = Http::asMultipart()->post($this->cloudinary_url, [
                    'file'          => fopen($file->getRealPath(), 'r'),
                    'upload_preset' => $this->preset,
                ]);

                if ($response->successful()) {
                    $validatedData['image'] = $response->json()['secure_url'];
                } else {
                    Log::error('Error En Cloudinary (Update): ' . $response->body());
                    return response()->json([
                        'error' => 'No se pudo actualizar la imagen en Cloudinary',
                        'detalle' => $response->json()
                    ], 400);
                }
            }

            $project->update($validatedData);
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
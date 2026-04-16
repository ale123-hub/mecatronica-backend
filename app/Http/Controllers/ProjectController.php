<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    private $cloudinary_url = "https://api.cloudinary.com/v1_1/dgdzygi4j/image/upload";
    private $preset = "preset_mecatronica";

    public function index()
    {
        $projects = Project::with(['semester', 'shift', 'students', 'teachers', 'images'])->get();
        return response()->json($projects);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {

            $data = $request->validate([
                'title'          => 'required|string|max:255',
                'description'    => 'nullable|string',
                'category'       => 'nullable|string|max:100',
                'semester_id'    => 'required|exists:semesters,id',
                'shift_id'       => 'required|exists:shifts,id',

                'image'          => 'nullable|image|max:10240',

                // extra images
                'extra_images'   => 'nullable|array',
                'extra_images.*' => 'image|max:10240',

                // NUEVO: prototype carousel
                'prototype_images'   => 'nullable|array',
                'prototype_images.*' => 'image|max:10240',
            ]);

            // 1. Imagen principal
            if ($request->hasFile('image')) {
                $data['image'] = $this->uploadToCloudinary($request->file('image'));
            }

            // 2. Crear proyecto
            $project = Project::create($data);

            // 3. Extra images
            if ($request->hasFile('extra_images')) {
                foreach ($request->file('extra_images') as $extraFile) {
                    $url = $this->uploadToCloudinary($extraFile);

                    if ($url) {
                        $project->images()->create([
                            'image_url' => $url
                        ]);
                    }
                }
            }

            // 4. PROTOTYPE IMAGES (CARRUSEL)
            if ($request->hasFile('prototype_images')) {
                foreach ($request->file('prototype_images') as $file) {
                    $url = $this->uploadToCloudinary($file);

                    if ($url) {
                        $project->images()->create([
                            'image_url' => $url
                        ]);
                    }
                }
            }

            $this->syncRelations($project, $request);

            return response()->json(
                $project->load(['images', 'semester', 'shift', 'students', 'teachers']),
                201
            );
        });
    }

    public function update(Request $request, Project $project)
    {
        return DB::transaction(function () use ($request, $project) {

            $data = $request->validate([
                'title'          => 'sometimes|required|string|max:255',
                'description'    => 'nullable|string',
                'category'       => 'nullable|string|max:100',
                'semester_id'    => 'sometimes|required|exists:semesters,id',
                'shift_id'       => 'sometimes|required|exists:shifts,id',

                'image'          => 'nullable|image|max:10240',

                'extra_images'   => 'nullable|array',
                'extra_images.*' => 'image|max:10240',

                // NUEVO
                'prototype_images'   => 'nullable|array',
                'prototype_images.*' => 'image|max:10240',
            ]);

            // principal
            if ($request->hasFile('image')) {
                $data['image'] = $this->uploadToCloudinary($request->file('image'));
            }

            $project->update($data);

            // extra images
            if ($request->hasFile('extra_images')) {
                foreach ($request->file('extra_images') as $extraFile) {
                    $url = $this->uploadToCloudinary($extraFile);

                    if ($url) {
                        $project->images()->create([
                            'image_url' => $url
                        ]);
                    }
                }
            }

            // prototype images
            if ($request->hasFile('prototype_images')) {
                foreach ($request->file('prototype_images') as $file) {
                    $url = $this->uploadToCloudinary($file);

                    if ($url) {
                        $project->images()->create([
                            'image_url' => $url
                        ]);
                    }
                }
            }

            $this->syncRelations($project, $request);

            return response()->json(
                $project->load(['semester', 'shift', 'students', 'teachers', 'images'])
            );
        });
    }

    private function uploadToCloudinary($file)
    {
        $response = Http::post($this->cloudinary_url, [
            'file' => 'data:image/' . $file->getClientOriginalExtension() .
                ';base64,' . base64_encode(file_get_contents($file->getRealPath())),
            'upload_preset' => $this->preset,
        ]);

        return $response->successful()
            ? $response->json()['secure_url']
            : null;
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
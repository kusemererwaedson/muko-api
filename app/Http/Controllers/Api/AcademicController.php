<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Term;
use Illuminate\Http\Request;

class AcademicController extends Controller
{
    // Levels
    public function levels(Request $request)
    {
        $query = Level::withCount('classes');
        
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }
        
        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }
        
        $perPage = $request->get('per_page', 15);
        return response()->json($query->paginate($perPage));
    }

    public function storeLevel(Request $request)
    {
        if ($request->isJson() && is_array($request->json()->all()) && isset($request->json()->all()[0])) {
            $levels = [];
            foreach ($request->json()->all() as $levelData) {
                $validated = validator($levelData, [
                    'name' => 'required|string|max:255',
                    'type' => 'required|in:Primary,Secondary,Advanced',
                    'description' => 'nullable|string|max:500',
                ])->validate();
                $levels[] = Level::create($validated);
            }
            return response()->json($levels, 201);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:Primary,Secondary,Advanced',
            'description' => 'nullable|string|max:500',
        ]);

        $level = Level::create($request->all());
        return response()->json($level, 201);
    }

    // Classes
    public function classes(Request $request)
    {
        $query = SchoolClass::with('level');
        
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }
        
        if ($request->has('level_id')) {
            $query->where('level_id', $request->get('level_id'));
        }
        
        if ($request->has('year_of_study')) {
            $query->where('year_of_study', $request->get('year_of_study'));
        }
        
        $perPage = $request->get('per_page', 15);
        return response()->json($query->paginate($perPage));
    }

    public function storeClass(Request $request)
    {
        if ($request->isJson() && is_array($request->json()->all()) && isset($request->json()->all()[0])) {
            $classes = [];
            foreach ($request->json()->all() as $classData) {
                $validated = validator($classData, [
                    'name' => 'required|string|max:255',
                    'level_id' => 'required|exists:levels,id',
                    'year_of_study' => 'required|integer|min:1',
                    'capacity' => 'nullable|integer|min:1',
                ])->validate();
                $classes[] = SchoolClass::create($validated);
            }
            return response()->json($classes, 201);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'level_id' => 'required|exists:levels,id',
            'year_of_study' => 'required|integer|min:1',
            'capacity' => 'nullable|integer|min:1',
        ]);

        $class = SchoolClass::create($request->all());
        return response()->json($class->load('level'), 201);
    }

    // Streams
    public function streams(Request $request)
    {
        $query = Stream::select('id', 'name');
        
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }
        
        return response()->json($query->get());
    }

    public function storeStream(Request $request)
    {
        if ($request->isJson() && is_array($request->json()->all()) && isset($request->json()->all()[0])) {
            $streams = [];
            foreach ($request->json()->all() as $streamData) {
                $validated = validator($streamData, [
                    'name' => 'required|string|max:255',
                ])->validate();
                $streams[] = Stream::create($validated);
            }
            return response()->json($streams, 201);
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $stream = Stream::create($request->all());
        return response()->json($stream, 201);
    }

    // Terms
    public function terms(Request $request)
    {
        $query = Term::query();
        
        if ($request->has('year')) {
            $query->where('year', $request->get('year'));
        }
        
        if ($request->has('is_current')) {
            $query->where('is_current', $request->get('is_current'));
        }
        
        $perPage = $request->get('per_page', 15);
        return response()->json($query->orderBy('year', 'desc')->orderBy('name')->paginate($perPage));
    }

    public function storeTerm(Request $request)
    {
        $request->validate([
            'name' => 'required|in:Term 1,Term 2,Term 3',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'year' => 'required|integer|min:2020|max:2030',
            'is_current' => 'boolean',
        ]);

        if ($request->is_current) {
            Term::where('is_current', true)->update(['is_current' => false]);
        }

        $term = Term::create($request->all());
        return response()->json($term, 201);
    }

    public function setCurrentTerm($id)
    {
        Term::where('is_current', true)->update(['is_current' => false]);
        $term = Term::findOrFail($id);
        $term->update(['is_current' => true]);
        
        return response()->json($term);
    }

    // Class-Stream Relationships
    public function attachStream($classId, Request $request)
    {
        $request->validate([
            'stream_id' => 'required|exists:streams,id',
        ]);

        $class = SchoolClass::findOrFail($classId);
        $class->streams()->attach($request->stream_id);
        
        return response()->json(['message' => 'Stream attached to class successfully']);
    }

    public function detachStream($classId, $streamId)
    {
        $class = SchoolClass::findOrFail($classId);
        $class->streams()->detach($streamId);
        
        return response()->json(['message' => 'Stream detached from class successfully']);
    }
}
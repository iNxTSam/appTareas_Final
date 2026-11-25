<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'team_id' => ['sometimes', 'nullable', 'integer', 'exists:team,team_id'],
        ]);

        $teamIds = $request->user()->teams()->pluck('team.team_id');
        $query = Project::query()->whereIn('team_id', $teamIds)->orderBy('name');

        if ($request->filled('team_id')) {
            $query->where('team_id', $request->integer('team_id'));
        }

        return response()->json([
            'data' => $query->get()->map(static fn (Project $project): array => [
                'id' => $project->project_id,
                'team_id' => $project->team_id,
                'name' => $project->name,
                'color' => $project->color,
            ])->values(),
        ]);
    }
}

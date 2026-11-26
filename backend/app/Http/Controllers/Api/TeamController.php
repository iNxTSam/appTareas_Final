<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teams = $request->user()
            ->teams()
            ->withCount('members')
            ->orderBy('team.name')
            ->get();

        return response()->json([
            'data' => $teams->map(static fn (Team $team): array => [
                'id' => $team->team_id,
                'name' => $team->name,
                'slug' => $team->slug,
                'role' => $team->pivot->role,
                'members_count' => $team->members_count,
            ])->values(),
        ]);
    }
}

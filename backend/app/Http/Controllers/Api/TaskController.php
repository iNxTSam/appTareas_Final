<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    public function index(IndexTaskRequest $request): AnonymousResourceCollection
    {
        $filters = array_filter(
            $request->validated(),
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );

        $query = $request->user()->tasks();

        if (isset($filters['from'])) {
            $query->where('end_date', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->where('start_date', '<=', $filters['to']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return TaskResource::collection(
            $query->orderBy('start_date')->orderBy('id')->get(),
        );
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $request->user()->tasks()->create($request->validated());

        return (new TaskResource($task))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, int $taskId): TaskResource
    {
        return new TaskResource($request->user()->tasks()->findOrFail($taskId));
    }

    public function update(UpdateTaskRequest $request, int $task): TaskResource
    {
        $task = $request->user()->tasks()->findOrFail($task);
        $task->update($request->validated());

        return new TaskResource($task);
    }

    public function destroy(Request $request, int $task): Response
    {
        $request->user()->tasks()->findOrFail($task)->delete();

        return response()->noContent();
    }
}

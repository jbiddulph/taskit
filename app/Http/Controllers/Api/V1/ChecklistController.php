<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Todo;
use App\Models\TodoChecklistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChecklistController extends PlatformController
{
    public function index(Request $request, int $taskId): JsonResponse
    {
        $todo = $this->findCompanyTodo($request, $taskId);
        if (! $todo) {
            return $this->fail('Task not found.', 404);
        }

        $items = $todo->checklistItems()->get()->map(fn (TodoChecklistItem $item) => $this->transform($item));

        return $this->ok(['checklist' => $items]);
    }

    public function store(Request $request, int $taskId): JsonResponse
    {
        $todo = $this->findCompanyTodo($request, $taskId);
        if (! $todo) {
            return $this->fail('Task not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'position' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed.', 422, $validator->errors());
        }

        $position = $request->input(
            'position',
            (int) $todo->checklistItems()->max('position') + 1
        );

        $item = $todo->checklistItems()->create([
            'title' => $request->input('title'),
            'position' => $position,
            'completed' => false,
        ]);

        return $this->ok($this->transform($item), 'Checklist item created.', 201);
    }

    public function update(Request $request, int $taskId, int $itemId): JsonResponse
    {
        $todo = $this->findCompanyTodo($request, $taskId);
        if (! $todo) {
            return $this->fail('Task not found.', 404);
        }

        $item = $todo->checklistItems()->where('id', $itemId)->first();
        if (! $item) {
            return $this->fail('Checklist item not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'completed' => 'sometimes|boolean',
            'position' => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed.', 422, $validator->errors());
        }

        if ($request->has('title')) {
            $item->title = $request->input('title');
        }
        if ($request->has('position')) {
            $item->position = (int) $request->input('position');
        }
        if ($request->has('completed')) {
            if ($request->boolean('completed')) {
                $item->markCompleted($this->platformUser($request));
            } else {
                $item->markIncomplete();
            }
        } else {
            $item->save();
        }

        return $this->ok($this->transform($item->fresh()), 'Checklist item updated.');
    }

    public function destroy(Request $request, int $taskId, int $itemId): JsonResponse
    {
        $todo = $this->findCompanyTodo($request, $taskId);
        if (! $todo) {
            return $this->fail('Task not found.', 404);
        }

        $item = $todo->checklistItems()->where('id', $itemId)->first();
        if (! $item) {
            return $this->fail('Checklist item not found.', 404);
        }

        $item->delete();

        return $this->ok(null, 'Checklist item deleted.');
    }

    private function findCompanyTodo(Request $request, int $id): ?Todo
    {
        return Todo::query()
            ->forCompany($this->companyId($request))
            ->where('id', $id)
            ->first();
    }

    private function transform(TodoChecklistItem $item): array
    {
        return [
            'id' => $item->id,
            'task_id' => $item->todo_id,
            'title' => $item->title,
            'completed' => $item->completed,
            'position' => $item->position,
            'completed_at' => optional($item->completed_at)?->toIso8601String(),
            'completed_by' => $item->completed_by,
        ];
    }
}

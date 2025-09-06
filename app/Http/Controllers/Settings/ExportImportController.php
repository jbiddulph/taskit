<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Project;
use App\Models\Todo;
use App\Models\TodoComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ExportImportController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $user->load('company');
        $company = $user->company;

        return Inertia::render('settings/ExportImport', [
            'user' => $user,
            'company' => $company,
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'data_type' => 'required|in:all,projects,todos,comments',
            'format' => 'required|in:json,csv',
        ]);

        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            return back()->withErrors(['error' => 'No company found for export.']);
        }

        $dataType = $request->input('data_type');
        $format = $request->input('format');

        // Get data based on type
        $data = $this->getExportData($company, $dataType);
        
        // Generate filename
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "{$company->code}_{$dataType}_{$timestamp}.{$format}";

        // Export based on format
        if ($format === 'json') {
            return $this->exportJson($data, $filename);
        } else {
            return $this->exportCsv($data, $filename, $dataType);
        }
    }

    private function getExportData(Company $company, string $dataType): array
    {
        switch ($dataType) {
            case 'all':
                return [
                    'company' => $company->toArray(),
                    'users' => $company->users()->get()->toArray(),
                    'projects' => $this->getProjectsData($company),
                    'todos' => $this->getTodosData($company),
                    'comments' => $this->getCommentsData($company),
                    'exported_at' => now()->toISOString(),
                ];

            case 'projects':
                return $this->getProjectsData($company);

            case 'todos':
                return $this->getTodosData($company);

            case 'comments':
                return $this->getCommentsData($company);

            default:
                return [];
        }
    }

    private function getProjectsData(Company $company): array
    {
        return $company->projects()
            ->with(['owner'])
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'key' => $project->key,
                    'color' => $project->color,
                    'is_active' => $project->is_active,
                    'viewing_order' => $project->viewing_order,
                    'owner_name' => $project->owner->name,
                    'owner_email' => $project->owner->email,
                    'created_at' => $project->created_at,
                    'updated_at' => $project->updated_at,
                ];
            })
            ->toArray();
    }

    private function getTodosData(Company $company): array
    {
        $projectIds = $company->projects()->pluck('id');
        
        return Todo::whereIn('project_id', $projectIds)
            ->with(['project', 'user'])
            ->get()
            ->map(function ($todo) {
                return [
                    'id' => $todo->id,
                    'title' => $todo->title,
                    'description' => $todo->description,
                    'status' => $todo->status,
                    'priority' => $todo->priority,
                    'type' => $todo->type,
                    'tags' => $todo->tags,
                    'assignee' => $todo->assignee,
                    'due_date' => $todo->due_date,
                    'story_points' => $todo->story_points,
                    'project_name' => $todo->project->name,
                    'project_key' => $todo->project->key,
                    'created_by' => $todo->user?->name,
                    'created_by_email' => $todo->user?->email,
                    'created_at' => $todo->created_at,
                    'updated_at' => $todo->updated_at,
                ];
            })
            ->toArray();
    }

    private function getCommentsData(Company $company): array
    {
        $projectIds = $company->projects()->pluck('id');
        $todoIds = Todo::whereIn('project_id', $projectIds)->pluck('id');
        
        return TodoComment::whereIn('todo_id', $todoIds)
            ->with(['todo.project', 'user'])
            ->get()
            ->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'todo_title' => $comment->todo->title,
                    'project_name' => $comment->todo->project->name,
                    'project_key' => $comment->todo->project->key,
                    'user_name' => $comment->user->name,
                    'user_email' => $comment->user->email,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                ];
            })
            ->toArray();
    }

    private function exportJson(array $data, string $filename): Response
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function exportCsv(array $data, string $filename, string $dataType): Response
    {
        $csv = $this->arrayToCsv($data, $dataType);
        
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function arrayToCsv(array $data, string $dataType): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');
        
        if ($dataType === 'all') {
            // For 'all' export, create separate sections
            fputcsv($output, ['=== COMPANY DATA ===']);
            if (isset($data['company'])) {
                fputcsv($output, array_keys($data['company']));
                fputcsv($output, $this->convertRowForCsv($data['company']));
            }
            
            fputcsv($output, ['']);
            fputcsv($output, ['=== USERS DATA ===']);
            if (isset($data['users']) && !empty($data['users'])) {
                fputcsv($output, array_keys($data['users'][0]));
                foreach ($data['users'] as $user) {
                    fputcsv($output, $this->convertRowForCsv($user));
                }
            }

            fputcsv($output, ['']);
            fputcsv($output, ['=== PROJECTS DATA ===']);
            if (isset($data['projects']) && !empty($data['projects'])) {
                fputcsv($output, array_keys($data['projects'][0]));
                foreach ($data['projects'] as $project) {
                    fputcsv($output, $this->convertRowForCsv($project));
                }
            }

            fputcsv($output, ['']);
            fputcsv($output, ['=== TODOS DATA ===']);
            if (isset($data['todos']) && !empty($data['todos'])) {
                fputcsv($output, array_keys($data['todos'][0]));
                foreach ($data['todos'] as $todo) {
                    fputcsv($output, $this->convertRowForCsv($todo));
                }
            }

            fputcsv($output, ['']);
            fputcsv($output, ['=== COMMENTS DATA ===']);
            if (isset($data['comments']) && !empty($data['comments'])) {
                fputcsv($output, array_keys($data['comments'][0]));
                foreach ($data['comments'] as $comment) {
                    fputcsv($output, $this->convertRowForCsv($comment));
                }
            }
        } else {
            // For specific data types
            if (!empty($data)) {
                fputcsv($output, array_keys($data[0]));
                foreach ($data as $row) {
                    fputcsv($output, $this->convertRowForCsv($row));
                }
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json,csv,txt|max:10240', // 10MB max
            'import_type' => 'required|in:projects,todos,comments',
        ]);

        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            return back()->withErrors(['error' => 'No company found for import.']);
        }

        $file = $request->file('file');
        $importType = $request->input('import_type');
        
        try {
            DB::beginTransaction();

            $content = file_get_contents($file->getPathname());
            $extension = $file->getClientOriginalExtension();

            if ($extension === 'json') {
                $data = json_decode($content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON format');
                }
            } else {
                // Parse CSV
                $data = $this->parseCsv($content);
            }

            // Handle different data formats
            $processData = $this->normalizeImportData($data, $importType);
            
            // Debug: Log import data structure
            error_log("Import debug - Original data keys: " . json_encode(array_keys($data)));
            error_log("Import debug - Original data type: " . gettype($data));
            error_log("Import debug - Original data sample: " . json_encode(array_slice($data, 0, 2, true)));
            error_log("Import debug - Import type: " . $importType);
            error_log("Import debug - Processed data count: " . count($processData));
            error_log("Import debug - Processed data sample: " . json_encode(array_slice($processData, 0, 1, true)));
            
            $importedCount = $this->processImport($company, $processData, $importType, $user);

            DB::commit();

            if ($importedCount > 0) {
                return back()->with('success', "Successfully imported {$importedCount} {$importType}.");
            } else {
                return back()->withErrors(['error' => "No {$importType} were imported. Please check your file format and data."]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Import failed: ' . $e->getMessage()]);
        }
    }

    private function parseCsv(string $content): array
    {
        $lines = explode("\n", $content);
        $data = [];
        $headers = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '===') === 0) {
                continue; // Skip empty lines and section headers
            }

            $row = str_getcsv($line);
            
            if ($headers === null) {
                $headers = $row;
            } else {
                if (count($row) === count($headers)) {
                    $data[] = array_combine($headers, $row);
                }
            }
        }

        return $data;
    }

    private function normalizeImportData(array $data, string $importType): array
    {
        // Handle different data formats for import
        switch ($importType) {
            case 'todos':
                // If data has a 'todos' key, extract it
                if (isset($data['todos']) && is_array($data['todos'])) {
                    return $data['todos'];
                }
                // If data is already a flat array, use it as is
                return $data;

            case 'projects':
                // If data has a 'projects' key, extract it
                if (isset($data['projects']) && is_array($data['projects'])) {
                    return $data['projects'];
                }
                return $data;

            case 'comments':
                // If data has a 'comments' key, extract it
                if (isset($data['comments']) && is_array($data['comments'])) {
                    return $data['comments'];
                }
                return $data;

            default:
                return $data;
        }
    }

    private function processImport(Company $company, array $data, string $importType, User $user): int
    {
        $importedCount = 0;

        switch ($importType) {
            case 'projects':
                foreach ($data as $projectData) {
                    if (isset($projectData['name']) && !empty($projectData['name'])) {
                        Project::create([
                            'name' => $projectData['name'],
                            'description' => $projectData['description'] ?? '',
                            'key' => Project::generateUniqueKey($projectData['name']),
                            'color' => $projectData['color'] ?? '#3b82f6',
                            'is_active' => true,
                            'owner_id' => $user->id,
                            'viewing_order' => Project::getNextViewingOrder($user->id),
                        ]);
                        $importedCount++;
                    }
                }
                break;

            case 'todos':
                foreach ($data as $todoData) {
                    if (isset($todoData['title']) && !empty($todoData['title'])) {
                        // Find project by name or key
                        $project = null;
                        if (isset($todoData['project_key'])) {
                            $project = $company->projects()->where('key', $todoData['project_key'])->first();
                        } elseif (isset($todoData['project_name'])) {
                            $project = $company->projects()->where('name', $todoData['project_name'])->first();
                        }

                        if ($project) {
                            Todo::create([
                                'title' => $todoData['title'],
                                'description' => $todoData['description'] ?? '',
                                'status' => $todoData['status'] ?? 'todo',
                                'priority' => $todoData['priority'] ?? 'medium',
                                'type' => $todoData['type'] ?? 'task',
                                'tags' => isset($todoData['tags']) ? (is_array($todoData['tags']) ? $todoData['tags'] : json_decode($todoData['tags'], true)) : [],
                                'assignee' => $todoData['assignee'] ?? $user->name,
                                'due_date' => $todoData['due_date'] ?? null,
                                'story_points' => $todoData['story_points'] ?? null,
                                'project_id' => $project->id,
                                'user_id' => $user->id, // Creator of the todo
                            ]);
                            $importedCount++;
                        }
                    }
                }
                break;

            case 'comments':
                foreach ($data as $commentData) {
                    if (isset($commentData['content']) && !empty($commentData['content'])) {
                        // Find todo by title within company projects
                        $todo = null;
                        if (isset($commentData['todo_title'])) {
                            $projectIds = $company->projects()->pluck('id');
                            $todo = Todo::whereIn('project_id', $projectIds)
                                       ->where('title', $commentData['todo_title'])
                                       ->first();
                        }

                        if ($todo) {
                            TodoComment::create([
                                'content' => $commentData['content'],
                                'todo_id' => $todo->id,
                                'user_id' => $user->id,
                            ]);
                            $importedCount++;
                        }
                    }
                }
                break;
        }

        return $importedCount;
    }

    /**
     * Convert a data row to CSV-compatible format
     */
    private function convertRowForCsv(array $row): array
    {
        $csvRow = [];
        foreach ($row as $value) {
            if (is_array($value)) {
                $csvRow[] = json_encode($value);
            } elseif (is_null($value)) {
                $csvRow[] = '';
            } else {
                $csvRow[] = (string) $value;
            }
        }
        return $csvRow;
    }
}

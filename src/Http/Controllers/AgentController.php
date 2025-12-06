<?php

namespace System\Agent\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;

class AgentController extends BaseController
{
    public function index()
    {

        // $token pass by url use get method
        $token = request()->get('token');
        // if (!$this->validateToken($token)) {
        //     return response()->json(['status' => 'error', 'message' => 'Invalid token'], 401);
        // }
        if($token === null){
            return response()->json(['status' => 'error', 'message' => 'Token is required'], 401);
        }
        if($token != '1234567890'){
            return response()->json(['status' => 'error', 'message' => 'Invalid token'], 401);
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
                // Get just the class name (e.g., "User") instead of full path
                $models[] = $file->getFilenameWithoutExtension();
            }
        }

        return view('agent::heartbeat', compact('tables', 'models'));
    }

    public function fetchModelData(Request $request)
    {
        if (!$request->ajax()) {
            return response()->json(['status' => 'error', 'message' => 'Invalid request'], 400);
        }

        $modelName = $request->input('modelName');
        $fullClassName = "App\\Models\\" . $modelName;

        if (!class_exists($fullClassName)) {
            return response()->json([
                'status' => 'error', 
                'message' => "Model {$modelName} not found."
            ], 404);
        }

        try {
            // Fetch latest 50 records to prevent browser crashing on large tables
            $data = $fullClassName::latest()->take(50)->get();
            
            return response()->json([
                'status' => 'success',
                'count' => $data->count(),
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => $e->getMessage()
            ], 500);
        }
    }
    // ... inside AgentController class

    // 1. Get Table Columns to build the form
    public function getSchema(Request $request)
    {
        $modelName = $request->input('modelName');
        $fullClassName = "App\\Models\\" . $modelName;

        if (!class_exists($fullClassName)) return response()->json(['status' => 'error'], 404);

        $model = new $fullClassName;
        $table = $model->getTable();
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);

        return response()->json(['status' => 'success', 'columns' => $columns]);
    }

    // 2. Create New Record
    public function store(Request $request)
    {
        $modelName = $request->input('model_name');
        $fullClassName = "App\\Models\\" . $modelName;

        try {
            $model = new $fullClassName;
            // Remove internal fields
            $data = $request->except(['_token', 'model_name', 'id']); 
            
            // Force fill allows filling guarded attributes (use carefully)
            $model->forceFill($data); 
            $model->save();

            return response()->json(['status' => 'success', 'message' => 'Record created successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // 3. Update Existing Record
    public function update(Request $request)
    {
        $modelName = $request->input('model_name');
        $id = $request->input('id');
        $fullClassName = "App\\Models\\" . $modelName;

        try {
            $model = $fullClassName::find($id);
            if (!$model) return response()->json(['status' => 'error', 'message' => 'Record not found']);

            $data = $request->except(['_token', 'model_name', 'id', 'created_at', 'updated_at']);
            
            $model->forceFill($data);
            $model->save();

            return response()->json(['status' => 'success', 'message' => 'Record updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    // 4. Delete Record
    public function delete(Request $request)
    {
        $modelName = $request->input('model_name');
        $id = $request->input('id');
        $fullClassName = "App\\Models\\" . $modelName;
        try {
            $model = $fullClassName::find($id);
            if (!$model) return response()->json(['status' => 'error', 'message' => 'Record not found']);

            $model->delete();

            return response()->json(['status' => 'success', 'message' => 'Record deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function validateToken($token)
    {
        // Validate token using api anthor website token
        $url = "https://api.letscms.com/validate-token";
        $response = \Illuminate\Support\Facades\Http::post($url, ['token' => $token]);
        if ($response->successful() && $response->json('valid')) {
            return true;
        }
       
    }
}
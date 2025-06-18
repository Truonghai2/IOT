<?php

namespace App\Http\Controllers;

use App\Models\TrainingData;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TrainingController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'data' => 'required|array',
                'data.*.temperature' => 'required|numeric',
                'data.*.humidity' => 'required|numeric',
                'data.*.gas_value' => 'required|numeric',
                'data.*.dust_value' => 'required|numeric',
                'data.*.fire_sensor_status' => 'required|boolean',
                'data.*.label' => 'required|string',
                'data.*.timestamp' => 'required|date',
                'device_id' => 'required|exists:devices,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $samples = collect($request->data)->map(function ($sample) use ($request) {
                return array_merge($sample, [
                    'device_id' => $request->device_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            });

            TrainingData::insert($samples->toArray());

            return response()->json([
                'message' => 'Training data stored successfully',
                'count' => count($samples)
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error storing training data: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to store training data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $query = TrainingData::query();

            if ($request->has('device_id')) {
                $query->where('device_id', $request->device_id);
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('timestamp', [
                    $request->start_date,
                    $request->end_date
                ]);
            }

            $data = $query->orderBy('timestamp', 'desc')
                         ->paginate($request->get('per_page', 50));

            return response()->json($data);

        } catch (\Exception $e) {
            Log::error('Error retrieving training data: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to retrieve training data',
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 
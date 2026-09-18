<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TrafficLogController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'stream_id' => 'required|string',
            'camera_name' => 'required|string',
            'car_count' => 'required|integer',
            'motorcycle_count' => 'required|integer',
            'car_in' => 'nullable|integer',
            'car_out' => 'nullable|integer',
            'motorcycle_in' => 'nullable|integer',
            'motorcycle_out' => 'nullable|integer',
        ]);

        $log = \App\Models\TrafficLog::create($validated);

        return response()->json([
            'message' => 'Traffic log saved successfully',
            'data' => $log
        ], 201);
    }
}

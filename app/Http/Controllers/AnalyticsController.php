<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\TrafficLog;

class AnalyticsController extends Controller
{
    public function index()
    {
        return view('analytics.index');
    }
}

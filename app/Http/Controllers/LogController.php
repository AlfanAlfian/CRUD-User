<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LogController extends Controller
{
    /**
     * Handle log requests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'timestamp' => 'required',
            'method' => 'required',
            'url' => 'required',
            'status' => 'required',
            'message' => 'required'
        ]);

        $logEntry = sprintf(
            "[%s] %s %s - %s: %s\n",
            $request->timestamp,
            $request->method,
            $request->url,
            $request->status,
            $request->message
        );

        $logPath = storage_path('logs/api_activity.log');

        $logDir = dirname($logPath);
        if (!File::exists($logDir)) {
            File::makeDirectory($logDir, 0755, true);
        }

        File::append($logPath, $logEntry);

        return response()->json(['message' => 'Log recorded successfully']);
    }
}

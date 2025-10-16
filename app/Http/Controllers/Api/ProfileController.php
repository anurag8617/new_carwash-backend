<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function updateDeviceToken(Request $request)
    {
        $validatedData = $request->validate([
            'device_token' => 'required|string',
        ]);

        $user = $request->user();
        $user->device_token = $validatedData['device_token'];
        $user->save();

        return response()->json(['success' => true, 'message' => 'Device token updated.']);
    }
}
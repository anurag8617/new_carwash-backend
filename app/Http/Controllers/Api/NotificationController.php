<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // Fetch user's notifications (latest first)
        $notifications = $request->user()->notifications;
        return response()->json(['success' => true, 'data' => $notifications]);
    }

    public function markAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true, 'message' => 'Notifications marked as read']);
    }

    public function destroy(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);

        if ($notification) {
            $notification->delete();
            return response()->json(['success' => true, 'message' => 'Notification deleted successfully']);
        }

        return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
    }
}
<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index()
    {
        $user = auth()->user();
        $notifications = $user->notifications()->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'status' => true,
            'message' => __('messages.notifications_fetched'),
            'all_unread_count' => $user->unreadNotifications->count(),
            'notifications' => NotificationResource::collection($notifications),
        ]);
    }

    /**
     * Show a specific notification.
     */
    public function show($id)
    {
        $user = auth()->user();
        $notification = $user->notifications()->findOrFail($id);

        return response()->json([
            'status' => true,
            'notification' => new NotificationResource($notification),
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function update($id)
    {
        $user = auth()->user();
        $notification = $user->unreadNotifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'status' => true,
            'message' => __('messages.notification_marked_as_read'),
            'notification' => new NotificationResource($notification),
        ]);
    }

    /**
     * Delete a specific notification.
     */
    public function destroy($id)
    {
        $user = auth()->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json([
            'status' => true,
            'message' => __('messages.notification_deleted'),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $user = auth()->user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'status' => true,
            'message' => __('messages.all_notifications_marked_as_read'),
        ]);
    }
}
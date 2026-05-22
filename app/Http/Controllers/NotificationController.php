<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $userId = (int) session('user_id');

        $notifications = Notification::forUser($userId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $items = collect($notifications->items())->map(fn ($n) => [
            'id'         => $n->id,
            'type'       => $n->type,
            'title'      => $n->title,
            'message'    => $n->message,
            'link'       => $n->link,
            'created_at' => optional($n->created_at)->format('M j, Y g:i A'),
            'is_read'    => (bool) $n->is_read,
        ])->values();

        return response()->json([
            'success'       => true,
            'notifications' => $items,
            'unread_count'  => Notification::forUser($userId)->unread()->count(),
            'pagination'    => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'per_page'     => $notifications->perPage(),
                'total'        => $notifications->total(),
            ],
        ]);
    }

    public function markRead(Request $request, ?int $id = null): JsonResponse
    {
        $userId = (int) session('user_id');
        $id     = $id ?? (int) $request->input('notification_id', 0);

        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'Notification ID is required.'], 400);
        }

        $notification = Notification::forUser($userId)->find($id);

        if (!$notification) {
            return response()->json(['success' => false, 'message' => 'Notification not found.'], 404);
        }

        $notification->markAsRead();

        return response()->json(['success' => true, 'message' => 'Notification marked as read.']);
    }

    public function markAllRead(): JsonResponse
    {
        $userId  = (int) session('user_id');
        $updated = Notification::forUser($userId)->unread()->update(['is_read' => true]);

        return response()->json([
            'success'       => true,
            'message'       => 'All notifications marked as read.',
            'updated_count' => $updated,
        ]);
    }
}

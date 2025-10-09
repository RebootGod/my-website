<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller: Notifications
 * 
 * Security: XSS protected, CSRF protected
 * OWASP: Safe notification handling
 * 
 * @package App\Http\Controllers
 * @created 2025-10-09
 */
class NotificationController extends Controller
{
    /**
     * Constructor - Require authentication
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display all notifications
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get all notifications (unread first, then read)
        $notifications = $user->notifications()
            ->orderByRaw('read_at IS NULL DESC')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Show specific notification and mark as read
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show($id)
    {
        $user = Auth::user();
        
        // Security: Only allow user to access their own notifications
        $notification = $user->notifications()->findOrFail($id);

        // Mark as read if not already read
        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        // Redirect to action URL if exists
        if (isset($notification->data['action_url'])) {
            return redirect($notification->data['action_url']);
        }

        // Otherwise redirect back to notifications page
        return redirect()->route('notifications.index');
    }

    /**
     * Mark all notifications as read
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        $user->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read');
    }

    /**
     * Mark specific notification as read (AJAX)
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request, $id)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $user = Auth::user();
        
        // Security: Only allow user to access their own notifications
        $notification = $user->notifications()->findOrFail($id);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'unread_count' => $user->unreadNotifications->count()
        ]);
    }

    /**
     * Delete notification
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        // Security: Only allow user to delete their own notifications
        $notification = $user->notifications()->findOrFail($id);
        $notification->delete();

        return back()->with('success', 'Notification deleted');
    }

    /**
     * Delete all read notifications
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteAllRead()
    {
        $user = Auth::user();
        
        $user->notifications()
            ->whereNotNull('read_at')
            ->delete();

        return back()->with('success', 'All read notifications deleted');
    }
}

<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display the employee's notification list (In-App Notification Drawer FR-701 / US-601).
     */
    public function index(Request $request): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $filter = $request->query('filter', 'all');

        $query = $user->notifications()->orderBy('created_at', 'desc');

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->paginate(20)->withQueryString();
        $unreadCount = $user->unreadNotifications()->count();

        return view('mobile.notifications.index', [
            'user' => $user,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'activeFilter' => $filter,
        ]);
    }

    /**
     * Mark a single notification as read and optionally navigate to its target URL.
     */
    public function markRead(Request $request, string $id): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        // If redirect query or data url exists, redirect to it
        $targetUrl = $request->input('target_url') ?? ($notification->data['url'] ?? null);

        if ($targetUrl && is_string($targetUrl)) {
            return redirect($targetUrl);
        }

        return back()->with('success', 'Notifikasi telah ditandai sebagai dibaca.');
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $user->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi telah ditandai sebagai dibaca.');
    }
}

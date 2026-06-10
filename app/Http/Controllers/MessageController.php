<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function inbox()
    {
        $messages = Message::with('sender')
            ->where('receiver_user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return view('messages.inbox', compact('messages'));
    }

    public function sent()
    {
        $messages = Message::with('receiver')
            ->where('sender_user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return view('messages.sent', compact('messages'));
    }

    public function create()
    {
        $users = User::where('is_active', 1)->orderBy('username')->get();
        return view('messages.create', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'receiver_user_id' => 'required|exists:users,id',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:5000',
        ]);

        Message::create([
            'sender_user_id' => Auth::id(),
            'receiver_user_id' => $data['receiver_user_id'],
            'title' => $data['title'] ?? null,
            'content' => $data['content'],
            'is_read' => 0,
            'created_at' => Carbon::now(),
        ]);

        return redirect()->route('messages.sent')->with('success', 'Đã gửi tin nhắn');
    }

    public function show(Message $message)
    {
        $uid = Auth::id();
        if ($message->sender_user_id !== $uid && $message->receiver_user_id !== $uid) {
            abort(403);
        }

        if ($message->receiver_user_id === $uid && !$message->is_read) {
            $message->is_read = 1;
            $message->save();
        }

        $message->load(['sender', 'receiver']);
        return view('messages.show', compact('message'));
    }
}

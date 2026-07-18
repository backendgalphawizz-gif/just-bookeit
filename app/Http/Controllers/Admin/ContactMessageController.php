<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactMessageController extends AdminController
{
    protected string $permissionModule = 'contact_messages';

    public function index(Request $request): View
    {
        $messages = ContactMessage::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where(function ($query) use ($term) {
                    $query->where('email', 'like', $term)
                        ->orWhere('subject', 'like', $term)
                        ->orWhere('message', 'like', $term);
                });
            })
            ->when($request->filled('status') && in_array($request->string('status')->toString(), [ContactMessage::STATUS_UNREAD, ContactMessage::STATUS_READ], true), function ($query) use ($request) {
                $query->where('status', $request->string('status'));
            })
            ->when($request->filled('inquiry_type') && array_key_exists($request->string('inquiry_type')->toString(), ContactMessage::INQUIRY_TYPES), function ($query) use ($request) {
                $query->where('inquiry_type', $request->string('inquiry_type'));
            })
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->newestFirst()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => ContactMessage::query()->count(),
            'unread' => ContactMessage::query()->unread()->count(),
            'read' => ContactMessage::query()->where('status', ContactMessage::STATUS_READ)->count(),
        ];

        return view('admin.contact-messages.index', compact('messages', 'stats'));
    }

    public function show(ContactMessage $contactMessage): View
    {
        $admin = auth('admin')->user();
        $contactMessage->markAsRead($admin);

        return view('admin.contact-messages.show', [
            'message' => $contactMessage->fresh('readByAdmin'),
        ]);
    }

    public function markRead(ContactMessage $contactMessage): RedirectResponse
    {
        $this->authorizeAdmin('edit');
        $contactMessage->markAsRead(auth('admin')->user());

        return back()->with('success', 'Message marked as read.');
    }

    public function destroy(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->delete();

        return redirect()
            ->route('admin.contact-messages.index')
            ->with('success', 'Contact message deleted.');
    }
}

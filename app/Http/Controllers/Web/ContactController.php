<?php

namespace App\Http\Controllers\Web;

use App\Models\ContactMessage;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends WebController
{
    public function index(): View
    {
        return view('web.contact.index', [
            'supportEmail' => PlatformSetting::get('support_email'),
            'supportPhone' => PlatformSetting::get('support_phone'),
            'contactAddress' => PlatformSetting::get('contact_address'),
            'inquiryTypes' => ContactMessage::INQUIRY_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'inquiry_type' => ['required', 'string', 'in:'.implode(',', array_keys(ContactMessage::INQUIRY_TYPES))],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ], [
            'inquiry_type.required' => 'Please select an inquiry type.',
            'inquiry_type.in' => 'Please select a valid inquiry type.',
        ]);

        ContactMessage::query()->create([
            'inquiry_type' => $validated['inquiry_type'],
            'email' => $validated['email'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => ContactMessage::STATUS_UNREAD,
        ]);

        return redirect()
            ->route('web.contact')
            ->with('success', 'Thanks! Your message has been sent. We will get back to you soon.');
    }
}

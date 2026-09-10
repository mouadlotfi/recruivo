<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Notifications\ContactMessageReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public contact page. Messages are emailed to the operator
 * (config('mail.contact_address')); nothing is stored in the database.
 */
class ContactController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Contact/Show', [
            'contact_email' => config('mail.contact_address'),
            'labels' => [
                'title' => __('contact.title'),
                'summary' => __('contact.summary'),
                'name' => __('contact.name'),
                'email' => __('contact.email'),
                'message' => __('contact.message'),
                'message_hint' => __('contact.message_hint'),
                'send' => __('contact.send'),
                'sent' => __('contact.sent'),
                'sent_description' => __('contact.sent_description'),
                'direct' => __('contact.direct'),
                'direct_description' => __('contact.direct_description'),
                'response_time' => __('contact.response_time'),
            ],
            'meta' => [
                'title' => __('contact.title'),
                'description' => __('contact.summary'),
            ],
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        // Honeypot: a real visitor never sees this field. Answer exactly as if the
        // message had been sent so scripts get no signal to iterate on.
        if ($request->filled('contact_website_url')) {
            return back()->with('success', __('contact.sent'));
        }

        $data = $request->validated();

        Notification::route('mail', config('mail.contact_address'))
            ->notify(new ContactMessageReceived($data['name'], $data['email'], $data['message']));

        return back()->with('success', __('contact.sent'));
    }
}

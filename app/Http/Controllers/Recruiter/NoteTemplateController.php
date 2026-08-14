<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\RecruiterNoteTemplate;
use Illuminate\Http\Request;

class NoteTemplateController extends Controller
{
    public function index(Request $request)
    {
        $templates = $request->user()->noteTemplates()->orderBy('name')->get();

        return view('recruiter.note-templates.index', compact('templates'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $request->user()->noteTemplates()->create($data);

        return redirect()->route('recruiter.note-templates.index', ['locale' => app()->getLocale()])
            ->with('success', __('recruiter.template_saved'));
    }

    public function update(Request $request, string $locale, RecruiterNoteTemplate $template)
    {
        $this->authorizeOwnership($request, $template);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $template->update($data);

        return redirect()->route('recruiter.note-templates.index', ['locale' => app()->getLocale()])
            ->with('success', __('recruiter.template_updated'));
    }

    public function destroy(Request $request, string $locale, RecruiterNoteTemplate $template)
    {
        $this->authorizeOwnership($request, $template);

        $template->delete();

        return redirect()->route('recruiter.note-templates.index', ['locale' => app()->getLocale()])
            ->with('success', __('recruiter.template_deleted'));
    }

    private function authorizeOwnership(Request $request, RecruiterNoteTemplate $template): void
    {
        abort_unless($template->recruiter_id === $request->user()->id, 403);
    }
}

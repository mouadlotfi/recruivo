<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\RecruiterNoteTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NoteTemplateController extends Controller
{
    private const PAGE_LABELS = [
        'note_templates' => 'recruiter.note_templates',
        'note_templates_subtitle' => 'recruiter.note_templates_subtitle',
        'back_to_jobs_list' => 'recruiter.back_to_jobs_list',
        'new_template' => 'recruiter.new_template',
        'template_name' => 'recruiter.template_name',
        'template_body' => 'recruiter.template_body',
        'expand_template_body' => 'recruiter.expand_template_body',
        'save_template' => 'recruiter.save_template',
        'update_template' => 'recruiter.update_template',
        'delete_template' => 'recruiter.delete_template',
        'delete_template_confirm' => 'recruiter.delete_template_confirm',
        'no_templates_yet' => 'recruiter.no_templates_yet',
        'expand' => 'common.expand',
        'cancel' => 'common.cancel',
        'done' => 'common.done',
        'close' => 'common.close',
    ];

    public function index(Request $request)
    {
        $templates = $request->user()->noteTemplates()->orderBy('name')->get();

        return Inertia::render('Recruiter/NoteTemplates/Index', [
            'templates' => $templates->map(fn (RecruiterNoteTemplate $template): array => [
                'id' => $template->id,
                'name' => $template->name,
                'body' => $template->body,
                'update_url' => localized_route('recruiter.note-templates.update', $template),
                'destroy_url' => localized_route('recruiter.note-templates.destroy', $template),
            ])->values()->all(),
            'back_url' => $request->query('back') ?: localized_route('recruiter.jobs.index'),
            'labels' => collect(self::PAGE_LABELS)->mapWithKeys(
                fn (string $translation, string $key): array => [$key => __($translation)]
            )->all(),
        ]);
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

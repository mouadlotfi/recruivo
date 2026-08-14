@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-stone-900 dark:text-white sm:text-3xl">{{ __('recruiter.note_templates') }}</h1>
            <p class="mt-2 text-sm text-stone-600 dark:text-stone-400 sm:text-base">{{ __('recruiter.note_templates_subtitle') }}</p>
        </div>
        <a href="{{ localized_route('recruiter.jobs.index') }}" class="inline-flex shrink-0 items-center justify-center self-start rounded-xl bg-stone-100 px-4 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700">
            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
            {{ __('recruiter.back_to_jobs_list') }}
        </a>
    </div>

    @if(session('success')) <x-alert type="success">{{ session('success') }}</x-alert> @endif
    @if(session('error')) <x-alert type="error">{{ session('error') }}</x-alert> @endif

    <section class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
        <h2 class="text-lg font-semibold text-stone-900 dark:text-white">{{ __('recruiter.new_template') }}</h2>
        <form action="{{ localized_route('recruiter.note-templates.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="template-name" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('recruiter.template_name') }}</label>
                <input id="template-name" name="name" type="text" value="{{ old('name') }}" maxlength="100"
                    class="mt-1 w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500">
                @error('name') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="template-body" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('recruiter.template_body') }}</label>
                <textarea id="template-body" name="body" rows="4" maxlength="2000"
                    class="mt-1 w-full resize-y rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500">{{ old('body') }}</textarea>
                @error('body') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 dark:focus:ring-offset-stone-900">{{ __('recruiter.save_template') }}</button>
        </form>
    </section>

    @if($templates->isEmpty())
        <p class="text-sm text-stone-500 dark:text-stone-400">{{ __('recruiter.no_templates_yet') }}</p>
    @else
        <div class="space-y-4">
            @foreach($templates as $template)
                <article class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                    <form action="{{ localized_route('recruiter.note-templates.update', $template) }}" method="POST" class="space-y-4">
                        @csrf @method('PUT')
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label for="name-{{ $template->id }}" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('recruiter.template_name') }}</label>
                                <input id="name-{{ $template->id }}" name="name" type="text" value="{{ $template->name }}" maxlength="100"
                                    class="mt-1 w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500">
                                @error('name') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="body-{{ $template->id }}" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('recruiter.template_body') }}</label>
                                <textarea id="body-{{ $template->id }}" name="body" rows="3" maxlength="2000"
                                    class="mt-1 w-full resize-y rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500">{{ $template->body }}</textarea>
                                @error('body') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 dark:focus:ring-offset-stone-900">{{ __('recruiter.update_template') }}</button>
                        </div>
                    </form>
                    <form action="{{ localized_route('recruiter.note-templates.destroy', $template) }}" method="POST" class="mt-4 inline-flex" onsubmit="return confirm('{{ __('recruiter.delete_template_confirm') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-red-100 px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-200 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20">{{ __('recruiter.delete_template') }}</button>
                    </form>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection

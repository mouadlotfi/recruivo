@php
    $profile = $user->candidateProfile;
    $languageItems = old('languages_json') ? json_decode(old('languages_json'), true) : ($profile?->languages_data ?? []);
    $linkItems = old('links_json') ? json_decode(old('links_json'), true) : ($profile?->profile_links ?? []);
    $experienceItems = old('experiences_json') ? json_decode(old('experiences_json'), true) : ($profile?->experiences ?? []);
    $educationItems = old('educations_json') ? json_decode(old('educations_json'), true) : ($profile?->educations ?? []);
    $languages = collect(['Arabic', 'Chinese', 'Dutch', 'English', 'French', 'German', 'Hindi', 'Italian', 'Japanese', 'Korean', 'Portuguese', 'Russian', 'Spanish', 'Turkish'])
        ->merge(collect($languageItems)->pluck('language'))->filter()->unique()->sort()->values();
    $linkTypes = ['LinkedIn', 'X', 'GitHub', 'Personal Website', 'Instagram'];
    $normalizedLinkNames = ['Portfolio' => 'Personal Website', 'Website' => 'Personal Website', 'Twitter' => 'X'];
    $linkItems = collect($linkItems)->map(function ($link) use ($normalizedLinkNames) {
        $link['name'] = $normalizedLinkNames[$link['name']] ?? $link['name'];
        return $link;
    })->filter(fn ($link) => in_array($link['name'] ?? '', ['LinkedIn', 'X', 'GitHub', 'Personal Website', 'Instagram'], true))
      ->unique('name')->values()->all();
    $normalizeDate = fn ($date) => $date && preg_match('/^\d{4}$/', $date) ? $date.'-01' : $date;
    $experienceItems = collect($experienceItems)->map(function ($item) use ($normalizeDate) {
        $item['start_date'] = $normalizeDate($item['start_date'] ?? null);
        $item['end_date'] = $normalizeDate($item['end_date'] ?? null);
        return $item;
    })->all();
    $educationItems = collect($educationItems)->map(function ($item) use ($normalizeDate) {
        $item['start_date'] = $normalizeDate($item['start_date'] ?? null);
        $item['end_date'] = $normalizeDate($item['end_date'] ?? null);
        return $item;
    })->all();
    $currentYear = (int) date('Y');
    $currentAndPastYears = range($currentYear, $currentYear - 70);
    $futureEducationEndYears = range($currentYear + 5, $currentYear - 70);
    $months = collect(range(1, 12))->mapWithKeys(fn ($month) => [str_pad((string) $month, 2, '0', STR_PAD_LEFT) => \Carbon\Carbon::create(2000, $month)->translatedFormat('F')]);
    $inputClass = 'w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-800 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-950 dark:text-white';
    $labelClass = 'block text-sm font-medium text-stone-700 dark:text-stone-200';
@endphp

<div class="space-y-6">
    <section data-profile-collection="languages" x-data="profileCollection(@js($languageItems), { language: '', proficiency: 'intermediate' }, { requiredMessage: @js(__('profile.complete_required_fields')), proficiencyLabels: @js(collect(['beginner','elementary','intermediate','professional_working','fluent','native_bilingual'])->mapWithKeys(fn($level) => [$level => __('profile.proficiency_'.$level)])) })" class="rounded-2xl border border-stone-200/80 p-5 dark:border-stone-700">
        <input type="hidden" name="languages_json" :value="JSON.stringify(items)">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h3 class="font-semibold text-stone-900 dark:text-white">{{ __('profile.languages') }}</h3><p class="mt-1 text-sm text-stone-500">{{ __('profile.languages_help') }}</p></div>
            <button type="button" @click="add()" class="min-h-11 rounded-xl border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2 dark:border-amber-500/40 dark:text-amber-300 dark:focus-visible:ring-offset-stone-900">{{ __('profile.add_language') }}</button>
        </div>
        <div x-show="items.length" class="mt-4 space-y-3">
            <template x-for="(item, index) in items" :key="index">
                <article class="flex flex-col gap-3 rounded-xl bg-stone-50 p-4 dark:bg-stone-800/70 sm:flex-row sm:items-center sm:justify-between">
                    <div><p class="font-medium text-stone-900 dark:text-white" x-text="item.language"></p><p class="text-sm text-stone-500" x-text="proficiencyLabel(item.proficiency)"></p></div>
                    <div class="flex gap-2"><button type="button" @click="edit(index)" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-amber-700 dark:text-amber-300">{{ __('profile.edit_entry') }}</button><button type="button" @click="remove(index)" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-red-600 dark:text-red-400">{{ __('profile.remove_entry') }}</button></div>
                </article>
            </template>
        </div>
        <p x-show="!items.length && !editing" class="mt-4 rounded-xl bg-stone-50 p-4 text-sm text-stone-500 dark:bg-stone-800/70">{{ __('profile.no_languages') }}</p>
        <div x-show="editing" x-cloak class="mt-4 grid gap-4 rounded-xl border border-amber-200 bg-amber-50/50 p-4 dark:border-amber-500/30 dark:bg-amber-500/5 sm:grid-cols-2">
            <div><label class="{{ $labelClass }}">{{ __('profile.language_name') }}</label><select data-language-select x-model="draft.language" class="mt-1 {{ $inputClass }}"><option value="">{{ __('profile.select_language') }}</option>@foreach($languages as $language)<option value="{{ $language }}">{{ $language }}</option>@endforeach</select></div>
            <div><label class="{{ $labelClass }}">{{ __('profile.proficiency_level') }}</label><select x-model="draft.proficiency" class="mt-1 {{ $inputClass }}">@foreach(['beginner','elementary','intermediate','professional_working','fluent','native_bilingual'] as $level)<option value="{{ $level }}">{{ __('profile.proficiency_'.$level) }}</option>@endforeach</select></div>
            <p x-show="validationMessage" x-text="validationMessage" class="text-sm font-medium text-red-600 sm:col-span-2 dark:text-red-400"></p><div class="flex gap-2 sm:col-span-2"><button type="button" @click="save(['language', 'proficiency'])" class="min-h-11 rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white">{{ __('profile.save_entry') }}</button><button type="button" @click="cancel()" class="min-h-11 rounded-xl px-4 py-2 text-sm font-semibold text-stone-600 dark:text-stone-300">{{ __('profile.cancel') }}</button></div>
        </div>
    </section>

    <section data-profile-collection="links" x-data="profileCollection(@js($linkItems), { name: '', url: '' }, { requiredMessage: @js(__('profile.complete_required_fields')), duplicateMessage: @js(__('profile.link_type_unique')), uniqueField: 'name' })" class="rounded-2xl border border-stone-200/80 p-5 dark:border-stone-700">
        <input type="hidden" name="links_json" :value="JSON.stringify(items)">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h3 class="font-semibold text-stone-900 dark:text-white">{{ __('profile.links') }}</h3><p class="mt-1 text-sm text-stone-500">{{ __('profile.links_help') }}</p></div>
            <button type="button" @click="items.length < 5 && add()" :disabled="items.length >= 5" class="min-h-11 rounded-xl border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-amber-500/40 dark:text-amber-300">{{ __('profile.add_link') }}</button>
        </div>
        <div x-show="items.length" class="mt-4 space-y-3">
            <template x-for="(item, index) in items" :key="index"><article class="flex flex-col gap-3 rounded-xl bg-stone-50 p-4 dark:bg-stone-800/70 sm:flex-row sm:items-center sm:justify-between"><div class="min-w-0"><p class="font-medium text-stone-900 dark:text-white" x-text="item.name"></p><p class="truncate text-sm text-stone-500" x-text="item.url"></p></div><div class="flex gap-2"><button type="button" @click="edit(index)" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-amber-700 dark:text-amber-300">{{ __('profile.edit_entry') }}</button><button type="button" @click="remove(index)" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-red-600 dark:text-red-400">{{ __('profile.remove_entry') }}</button></div></article></template>
        </div>
        <p x-show="!items.length && !editing" class="mt-4 rounded-xl bg-stone-50 p-4 text-sm text-stone-500 dark:bg-stone-800/70">{{ __('profile.no_links') }}</p>
        <div x-show="editing" x-cloak class="mt-4 grid gap-4 rounded-xl border border-amber-200 bg-amber-50/50 p-4 dark:border-amber-500/30 dark:bg-amber-500/5 sm:grid-cols-2">
            <div><label class="{{ $labelClass }}">{{ __('profile.link_name') }}</label><select data-link-name-select x-model="draft.name" class="mt-1 {{ $inputClass }}"><option value="">{{ __('profile.select_link_type') }}</option>@foreach($linkTypes as $linkType)<option value="{{ $linkType }}" :disabled="isValueUsed('name', @js($linkType))">{{ $linkType === 'Personal Website' ? __('profile.personal_website') : $linkType }}</option>@endforeach</select></div>
            <div><label class="{{ $labelClass }}">{{ __('profile.link_url') }}</label><input x-model.trim="draft.url" type="url" inputmode="url" placeholder="https://" maxlength="500" class="mt-1 {{ $inputClass }}"></div>
            <p x-show="validationMessage" x-text="validationMessage" class="text-sm font-medium text-red-600 sm:col-span-2 dark:text-red-400"></p><div class="flex gap-2 sm:col-span-2"><button type="button" @click="save(['name', 'url'])" class="min-h-11 rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white">{{ __('profile.save_entry') }}</button><button type="button" @click="cancel()" class="min-h-11 rounded-xl px-4 py-2 text-sm font-semibold text-stone-600 dark:text-stone-300">{{ __('profile.cancel') }}</button></div>
        </div>
        <p class="mt-3 text-xs text-stone-500" x-text="`${items.length}/5 {{ __('profile.links_used') }}`"></p>
    </section>

    <section id="experience" data-profile-collection="experiences" x-data="profileCollection(@js($experienceItems), { job_title: '', company_name: '', location: '', start_date: '', end_date: '', is_current: false, description: '' }, { present: @js(__('profile.present')), requiredMessage: @js(__('profile.complete_required_fields')), dateOrderFields: ['start_date', 'end_date'], dateOrderMessage: @js(__('profile.end_date_after_start')) })" class="rounded-2xl border border-stone-200/80 p-5 dark:border-stone-700">
        <input type="hidden" name="experiences_json" :value="JSON.stringify(items)">
        <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-semibold text-stone-900 dark:text-white">{{ __('profile.experience') }}</h3><p class="mt-1 text-sm text-stone-500">{{ __('profile.experience_help') }}</p></div><button type="button" @click="add()" class="min-h-11 rounded-xl border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-700 dark:border-amber-500/40 dark:text-amber-300">{{ __('profile.add_experience') }}</button></div>
        <div x-show="items.length" class="mt-4 space-y-3"><template x-for="(item,index) in items" :key="index"><article class="rounded-xl bg-stone-50 p-4 dark:bg-stone-800/70"><div class="flex flex-col gap-3 sm:flex-row sm:justify-between"><div><h4 class="font-semibold text-stone-900 dark:text-white" x-text="item.job_title"></h4><p class="text-sm text-stone-700 dark:text-stone-300"><span x-text="item.company_name"></span><span x-show="item.location"> · <span x-text="item.location"></span></span></p><p class="mt-1 text-sm text-stone-500" x-text="dateRange(item.start_date,item.end_date,item.is_current)"></p></div><div class="flex gap-2"><button type="button" @click="edit(index)" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-amber-700 dark:text-amber-300">{{ __('profile.edit_entry') }}</button><button type="button" @click="remove(index)" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-red-600 dark:text-red-400">{{ __('profile.remove_entry') }}</button></div></div><p x-show="item.description" class="mt-3 whitespace-pre-line text-sm text-stone-600 dark:text-stone-400" x-text="item.description"></p></article></template></div>
        <p x-show="!items.length && !editing" class="mt-4 rounded-xl bg-stone-50 p-4 text-sm text-stone-500 dark:bg-stone-800/70">{{ __('profile.no_experience') }}</p>
        <div x-show="editing" x-cloak class="mt-4 grid gap-4 rounded-xl border border-amber-200 bg-amber-50/50 p-4 dark:border-amber-500/30 dark:bg-amber-500/5 sm:grid-cols-2">
            <div><label class="{{ $labelClass }}">{{ __('profile.job_title') }}</label><input x-model.trim="draft.job_title" type="text" maxlength="150" class="mt-1 {{ $inputClass }}"></div><div><label class="{{ $labelClass }}">{{ __('profile.company_name_entry') }}</label><input x-model.trim="draft.company_name" type="text" maxlength="150" class="mt-1 {{ $inputClass }}"></div>
            <div class="sm:col-span-2"><label class="{{ $labelClass }}">{{ __('profile.location') }}</label><input x-model.trim="draft.location" type="text" maxlength="150" class="mt-1 {{ $inputClass }}"></div>
            @include('profile.partials.month-year-select', ['field' => 'start_date', 'label' => __('profile.start_date'), 'years' => $currentAndPastYears, 'yearPolicy' => 'experience-start-current-max'])
            @include('profile.partials.month-year-select', ['field' => 'end_date', 'label' => __('profile.end_date'), 'years' => $currentAndPastYears, 'yearPolicy' => 'experience-end-current-max', 'disabledExpression' => 'draft.is_current'])
            <label class="flex min-h-11 items-center gap-3 sm:col-span-2"><input x-model="draft.is_current" @change="draft.is_current && clearDate('end_date')" type="checkbox" class="rounded border-stone-300 text-amber-600 focus:ring-amber-500"><span class="text-sm text-stone-700 dark:text-stone-300">{{ __('profile.currently_work_here') }}</span></label>
            <div class="sm:col-span-2"><label class="{{ $labelClass }}">{{ __('profile.description_responsibilities') }}</label><textarea x-model.trim="draft.description" rows="4" maxlength="3000" class="mt-1 {{ $inputClass }}"></textarea></div>
            <p x-show="validationMessage" x-text="validationMessage" class="text-sm font-medium text-red-600 sm:col-span-2 dark:text-red-400"></p><div class="flex gap-2 sm:col-span-2"><button type="button" @click="save(['job_title','company_name','start_date'], !draft.is_current ? ['end_date'] : [])" class="min-h-11 rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white">{{ __('profile.save_entry') }}</button><button type="button" @click="cancel()" class="min-h-11 rounded-xl px-4 py-2 text-sm font-semibold text-stone-600 dark:text-stone-300">{{ __('profile.cancel') }}</button></div>
        </div>
    </section>

    <section data-profile-collection="educations" x-data="profileCollection(@js($educationItems), { school: '', degree: '', field_of_study: '', start_date: '', end_date: '', is_current: false, description: '' }, { present: @js(__('profile.present')), requiredMessage: @js(__('profile.complete_required_fields')), dateOrderFields: ['start_date', 'end_date'], dateOrderMessage: @js(__('profile.end_date_after_start')) })" class="rounded-2xl border border-stone-200/80 p-5 dark:border-stone-700">
        <input type="hidden" name="educations_json" :value="JSON.stringify(items)">
        <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-semibold text-stone-900 dark:text-white">{{ __('profile.education') }}</h3><p class="mt-1 text-sm text-stone-500">{{ __('profile.education_help') }}</p></div><button type="button" @click="add()" class="min-h-11 rounded-xl border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-700 dark:border-amber-500/40 dark:text-amber-300">{{ __('profile.add_education') }}</button></div>
        <div x-show="items.length" class="mt-4 space-y-3"><template x-for="(item,index) in items" :key="index"><article class="rounded-xl bg-stone-50 p-4 dark:bg-stone-800/70"><div class="flex flex-col gap-3 sm:flex-row sm:justify-between"><div><h4 class="font-semibold text-stone-900 dark:text-white" x-text="item.school"></h4><p class="text-sm text-stone-700 dark:text-stone-300"><span x-text="item.degree"></span> · <span x-text="item.field_of_study"></span></p><p class="mt-1 text-sm text-stone-500" x-text="dateRange(item.start_date,item.end_date,item.is_current)"></p></div><div class="flex gap-2"><button type="button" @click="edit(index)" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-amber-700 dark:text-amber-300">{{ __('profile.edit_entry') }}</button><button type="button" @click="remove(index)" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-red-600 dark:text-red-400">{{ __('profile.remove_entry') }}</button></div></div><p x-show="item.description" class="mt-3 whitespace-pre-line text-sm text-stone-600 dark:text-stone-400" x-text="item.description"></p></article></template></div>
        <p x-show="!items.length && !editing" class="mt-4 rounded-xl bg-stone-50 p-4 text-sm text-stone-500 dark:bg-stone-800/70">{{ __('profile.no_education') }}</p>
        <div x-show="editing" x-cloak class="mt-4 grid gap-4 rounded-xl border border-amber-200 bg-amber-50/50 p-4 dark:border-amber-500/30 dark:bg-amber-500/5 sm:grid-cols-2">
            <div class="sm:col-span-2"><label class="{{ $labelClass }}">{{ __('profile.school') }}</label><input x-model.trim="draft.school" type="text" maxlength="150" class="mt-1 {{ $inputClass }}"></div><div><label class="{{ $labelClass }}">{{ __('profile.degree') }}</label><input x-model.trim="draft.degree" type="text" maxlength="150" class="mt-1 {{ $inputClass }}"></div><div><label class="{{ $labelClass }}">{{ __('profile.field_of_study') }}</label><input x-model.trim="draft.field_of_study" type="text" maxlength="150" class="mt-1 {{ $inputClass }}"></div>
            @include('profile.partials.month-year-select', ['field' => 'start_date', 'label' => __('profile.start_date'), 'years' => $currentAndPastYears, 'yearPolicy' => 'education-start-current-max'])
            @include('profile.partials.month-year-select', ['field' => 'end_date', 'label' => __('profile.end_date'), 'years' => $futureEducationEndYears, 'yearPolicy' => 'education-end-future-allowed', 'disabledExpression' => 'draft.is_current'])
            <label class="flex min-h-11 items-center gap-3 sm:col-span-2"><input x-model="draft.is_current" @change="draft.is_current && clearDate('end_date')" type="checkbox" class="rounded border-stone-300 text-amber-600 focus:ring-amber-500"><span class="text-sm text-stone-700 dark:text-stone-300">{{ __('profile.currently_studying_here') }}</span></label>
            <div class="sm:col-span-2"><label class="{{ $labelClass }}">{{ __('profile.additional_information') }} <span class="font-normal text-stone-500">({{ __('profile.optional') }})</span></label><textarea x-model.trim="draft.description" rows="4" maxlength="3000" class="mt-1 {{ $inputClass }}"></textarea><p class="mt-1 text-xs text-stone-500">{{ __('profile.education_description_optional_help') }}</p></div>
            <p x-show="validationMessage" x-text="validationMessage" class="text-sm font-medium text-red-600 sm:col-span-2 dark:text-red-400"></p><div class="flex gap-2 sm:col-span-2"><button type="button" @click="save(['school','degree','field_of_study','start_date'])" class="min-h-11 rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white">{{ __('profile.save_entry') }}</button><button type="button" @click="cancel()" class="min-h-11 rounded-xl px-4 py-2 text-sm font-semibold text-stone-600 dark:text-stone-300">{{ __('profile.cancel') }}</button></div>
        </div>
    </section>
</div>



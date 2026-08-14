<div x-data="{ showPreferences: true }">
    <template x-teleport="body">
        <div data-preferences-modal x-show="showPreferences" x-cloak @keydown.escape.window="showPreferences = false" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div x-show="showPreferences" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-stone-900/75 backdrop-blur-sm transition-opacity" @click="showPreferences = false"></div>

                <!-- Center modal -->
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>

                <!-- Modal panel -->
                <div x-show="showPreferences" x-trap.noscroll="showPreferences" role="dialog" aria-modal="true" aria-labelledby="preferences-modal-title" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block transform overflow-hidden rounded-2xl border border-stone-200/60 bg-white/95 text-left align-bottom shadow-2xl backdrop-blur transition-all dark:border-stone-700/60 dark:bg-stone-900/95 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <div class="p-6 sm:p-8">
                        <div class="text-center">
                            <h3 id="preferences-modal-title" class="text-xl font-semibold text-stone-900 dark:text-white">
                                {{ __('profile.preferences_modal_title') }}
                            </h3>
                            <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">
                                {{ __('profile.preferences_modal_help') }}
                            </p>
                        </div>
                        <form method="POST" action="{{ localized_route('candidate.preferences.quick') }}" class="mt-5">
                            @csrf
                            <div class="grid max-h-72 gap-2 overflow-y-auto pr-1 sm:grid-cols-2">
                                @foreach(\App\Enums\ItCategory::cases() as $category)
                                    <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-stone-200 bg-white/60 px-3 py-2 text-sm text-stone-700 transition hover:border-amber-300 dark:border-stone-700 dark:bg-stone-900/60 dark:text-stone-200">
                                        <input type="checkbox" name="preferred_categories[]" value="{{ $category->value }}"
                                            @checked(in_array($category->value, auth()->user()->candidateProfile?->preferred_categories ?? []))
                                            class="h-5 w-5 rounded border-stone-300 text-amber-600 focus:ring-amber-500 dark:border-stone-600">
                                        {{ $category->value }}
                                    </label>
                                @endforeach
                            </div>
                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
                                    {{ __('profile.save_preferences') }}
                                </button>
                            </div>
                        </form>
                        <form method="POST" action="{{ localized_route('candidate.preferences.quick') }}" class="mt-3 text-center">
                            @csrf
                            <input type="hidden" name="skip" value="1">
                            <button type="submit" class="text-sm font-medium text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200">
                                {{ __('profile.skip_for_now') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

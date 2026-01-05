<footer class="border-t border-stone-200 bg-white/70 py-6 text-sm text-stone-500 backdrop-blur dark:border-stone-800 dark:bg-stone-950/80 dark:text-stone-400">
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-center gap-3 px-4 text-center sm:flex-row sm:justify-between sm:gap-2 sm:px-6 sm:text-left">
        <p class="max-w-md">{{ __('common.footer_text', ['year' => date('Y')]) }}</p>
        <div class="flex items-center gap-4 whitespace-nowrap">
            <a
                href="https://mouadlotfi.com"
                class="transition hover:text-amber-600 dark:hover:text-amber-400"
                target="_blank"
                rel="noopener noreferrer"
            >
                Portfolio
            </a>
            <a
                href="https://www.linkedin.com/in/mouad-lotfi/"
                class="transition hover:text-amber-600 dark:hover:text-amber-400"
                target="_blank"
                rel="noopener noreferrer"
            >
                LinkedIn
            </a>
            <a
                href="mailto:mouad.lotfi.work@gmail.com"
                class="transition hover:text-amber-600 dark:hover:text-amber-400"
            >
                {{ __('common.contact') }}
            </a>
        </div>
    </div>
</footer>


function initProgressiveLoading(container) {
    const items = container.querySelector('[data-infinite-items]');

    if (!items || !container.dataset.nextUrl) {
        return;
    }

    const controls = document.createElement('div');
    controls.className = 'mt-8 flex min-h-12 items-center justify-center';
    controls.innerHTML = `
        <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-full bg-amber-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 dark:focus:ring-offset-stone-950" data-show-more></button>
        <div class="hidden items-center gap-3 text-sm text-stone-500 dark:text-stone-400" data-infinite-loading role="status">
            <svg class="h-5 w-5 animate-spin text-amber-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span data-infinite-loading-label></span>
        </div>
        <button type="button" class="hidden min-h-11 rounded-full border border-red-300 px-5 py-2.5 text-sm font-semibold text-red-700 transition hover:border-red-400 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 dark:border-red-500/40 dark:text-red-300" data-infinite-retry></button>
    `;

    const showMore = controls.querySelector('[data-show-more]');
    const loading = controls.querySelector('[data-infinite-loading]');
    const retry = controls.querySelector('[data-infinite-retry]');
    showMore.textContent = container.dataset.showMoreLabel || 'Show more';
    controls.querySelector('[data-infinite-loading-label]').textContent = container.dataset.loadingLabel || 'Loading more…';
    retry.textContent = container.dataset.retryLabel || 'Could not load more. Try again';
    container.append(controls);

    let nextUrl = container.dataset.nextUrl;
    let isLoading = false;

    const finish = () => {
        controls.remove();
    };

    const loadNextPage = async () => {
        if (isLoading || !nextUrl) return;

        isLoading = true;
        showMore.classList.add('hidden');
        retry.classList.add('hidden');
        loading.classList.remove('hidden');
        loading.classList.add('flex');

        try {
            const expectsJson = container.dataset.infiniteResponse === 'json';
            const response = await fetch(nextUrl, {
                credentials: 'same-origin',
                headers: {
                    Accept: expectsJson ? 'application/json' : 'text/html',
                    ...(expectsJson ? { 'X-Infinite-Scroll': '1' } : {}),
                },
            });

            if (!response.ok) {
                throw new Error(`Failed to load more results: ${response.status}`);
            }

            if (expectsJson) {
                const page = await response.json();
                const template = document.createElement('template');
                template.innerHTML = page.html.trim();
                items.append(template.content);
                nextUrl = page.next_url ?? '';
            } else {
                const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                const key = container.dataset.infiniteKey;
                const remoteContainer = Array.from(page.querySelectorAll('[data-infinite-scroll]'))
                    .find(candidate => candidate.dataset.infiniteKey === key);
                const remoteItems = remoteContainer?.querySelector('[data-infinite-items]');

                if (!remoteContainer || !remoteItems) {
                    throw new Error(`Progressive-loading container "${key}" was not found in the response.`);
                }

                items.append(...Array.from(remoteItems.children));
                nextUrl = remoteContainer.dataset.nextUrl ?? '';
            }

            container.dataset.nextUrl = nextUrl;
            if (!nextUrl) finish();
        } catch (error) {
            retry.classList.remove('hidden');
            console.error(error);
        } finally {
            isLoading = false;
            loading.classList.add('hidden');
            loading.classList.remove('flex');
            if (nextUrl && retry.classList.contains('hidden')) {
                showMore.classList.remove('hidden');
            }
        }
    };

    showMore.addEventListener('click', loadNextPage);
    retry.addEventListener('click', loadNextPage);
}

function bootProgressiveLoading() {
    document.querySelectorAll('[data-infinite-scroll]').forEach(initProgressiveLoading);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootProgressiveLoading);
} else {
    bootProgressiveLoading();
}

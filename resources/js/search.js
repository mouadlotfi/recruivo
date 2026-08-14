const SEARCH_CACHE = new Map();
const RECENT_KEY = 'recruivo:recent-searches';
let searchIdCounter = 0;

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.search-input').forEach(setupSearch);
});

function setupSearch(input) {
    const container = input.closest('.search-container');
    if (!container) return;

    const form = input.closest('form');
    const clearButton = container.querySelector('[data-search-clear]');
    const listbox = document.createElement('div');
    const listboxId = input.getAttribute('aria-controls') || nextSearchId('search-suggestions');
    listbox.id = listboxId;
    listbox.setAttribute('role', 'listbox');
    listbox.setAttribute('aria-live', 'polite');
    listbox.setAttribute('aria-atomic', 'false');
    listbox.className = 'search-suggestions absolute inset-x-0 top-full z-[10000] mt-2 hidden max-h-[min(70vh,34rem)] w-full overflow-y-auto rounded-2xl border border-stone-200 bg-white shadow-2xl shadow-stone-950/10 dark:border-stone-700 dark:bg-stone-900';
    container.appendChild(listbox);
    input.setAttribute('aria-controls', listboxId);

    let activeIndex = -1;
    let options = [];
    let debounceTimer = null;
    let controller = null;

    const syncClear = () => clearButton?.classList.toggle('hidden', input.value.length === 0);
    const close = () => {
        listbox.classList.add('hidden');
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        activeIndex = -1;
        options = [];
    };
    const open = () => {
        listbox.classList.remove('hidden');
        input.setAttribute('aria-expanded', 'true');
    };
    const refreshOptions = () => {
        options = [...listbox.querySelectorAll('[role="option"]')];
        activeIndex = -1;
    };
    const select = index => {
        if (!options.length) return;
        activeIndex = (index + options.length) % options.length;
        options.forEach((option, optionIndex) => {
            const active = optionIndex === activeIndex;
            option.setAttribute('aria-selected', String(active));
            option.classList.toggle('bg-amber-50', active);
            option.classList.toggle('dark:bg-amber-500/10', active);
        });
        const option = options[activeIndex];
        input.setAttribute('aria-activedescendant', option.id);
        option.scrollIntoView({ block: 'nearest' });
    };

    input.addEventListener('input', () => {
        syncClear();
        clearTimeout(debounceTimer);
        controller?.abort();
        const query = normalize(input.value);

        if (!query) {
            renderRecent(listbox, container, input);
            refreshOptions();
            listbox.children.length ? open() : close();
            return;
        }

        renderLoading(listbox);
        open();
        debounceTimer = setTimeout(async () => {
            controller = new AbortController();
            try {
                const data = await fetchSuggestions(query, controller.signal);
                if (normalize(input.value) !== data.query) return;
                renderSuggestions(data, listbox, container, input);
                refreshOptions();
                open();
            } catch (error) {
                if (error.name === 'AbortError') return;
                renderMessage(listbox, container.dataset.searchError || 'Search is temporarily unavailable.');
                open();
            }
        }, 180);
    });

    input.addEventListener('focus', () => {
        syncClear();
        if (!input.value.trim()) {
            renderRecent(listbox, container, input);
            refreshOptions();
            if (listbox.children.length) open();
        }
    });

    input.addEventListener('search-options-updated', () => {
        refreshOptions();
        if (!listbox.querySelector('[role="option"]')) close();
    });

    input.addEventListener('keydown', event => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (listbox.classList.contains('hidden')) open();
            select(activeIndex + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            select(activeIndex - 1);
        } else if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();
            options[activeIndex].click();
        } else if (event.key === 'Escape') {
            event.preventDefault();
            close();
        }
    });

    clearButton?.addEventListener('click', () => {
        input.value = '';
        syncClear();
        close();
        input.focus();
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });

    form?.addEventListener('submit', () => rememberSearch(input.value));
    document.addEventListener('click', event => {
        if (!container.contains(event.target)) close();
    });

    syncClear();
}

async function fetchSuggestions(query, signal) {
    if (SEARCH_CACHE.has(query)) return SEARCH_CACHE.get(query);
    const response = await fetch(`/api/search/suggestions?q=${encodeURIComponent(query)}`, {
        signal,
        headers: { Accept: 'application/json' },
    });
    if (!response.ok) throw new Error(`Search failed: ${response.status}`);
    const data = await response.json();
    SEARCH_CACHE.set(query, data);
    return data;
}

function renderSuggestions(data, listbox, container, input) {
    const sections = data.sections || [];
    if (!sections.length) {
        listbox.innerHTML = `
            <div class="p-5">
                <p class="font-medium text-stone-900 dark:text-white">${escapeHtml(container.dataset.searchNoResults || 'No direct matches')}</p>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">${escapeHtml(input.value.trim())}</p>
            </div>
            ${searchAllOption(data.search_url, container.dataset.searchAllLabel, data.query, 0)}
        `;
        return;
    }

    let optionIndex = 0;
    listbox.innerHTML = sections.map(section => {
        const items = section.items.map(item => resultOption(item, data.query, optionIndex++)).join('');
        return `<section aria-labelledby="${escapeHtml(section.type)}-heading">
            <h2 id="${escapeHtml(section.type)}-heading" class="sticky top-0 border-b border-stone-100 bg-stone-50/95 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-stone-500 backdrop-blur dark:border-stone-800 dark:bg-stone-900/95 dark:text-stone-400">${escapeHtml(section.label)}</h2>
            ${items}
        </section>`;
    }).join('') + searchAllOption(data.search_url, container.dataset.searchAllLabel, data.query, optionIndex);
}

function resultOption(item, query, index) {
    const id = nextSearchId('search-option');
    const url = safeUrl(item.url);
    const logo = safeUrl(item.logo);
    return `<a id="${id}" role="option" aria-selected="false" data-search-option href="${escapeHtml(url)}" class="flex min-h-14 items-center gap-3 border-b border-stone-100 px-4 py-3 transition hover:bg-stone-50 focus:bg-amber-50 focus:outline-none dark:border-stone-800 dark:hover:bg-stone-800/70 dark:focus:bg-amber-500/10">
        ${logo ? `<img src="${escapeHtml(logo)}" alt="" class="h-10 w-10 shrink-0 rounded-lg object-cover">` : `<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-100 font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">${escapeHtml(item.title.charAt(0).toUpperCase())}</span>`}
        <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-semibold text-stone-900 dark:text-white">${highlight(item.title, query)}</span>
            <span class="mt-0.5 block truncate text-xs text-stone-500 dark:text-stone-400">${highlight(item.subtitle, query)}</span>
        </span>
    </a>`;
}

function searchAllOption(url, label, query, index) {
    return `<a id="search-option-all-${index}" role="option" aria-selected="false" data-search-option href="${escapeHtml(safeUrl(url))}" class="flex min-h-12 items-center justify-between bg-stone-50 px-4 py-3 text-sm font-semibold text-amber-700 transition hover:bg-amber-50 focus:bg-amber-50 focus:outline-none dark:bg-stone-800/50 dark:text-amber-300 dark:hover:bg-amber-500/10 dark:focus:bg-amber-500/10">
        <span>${escapeHtml(label || 'See all results')} “${escapeHtml(query)}”</span><span aria-hidden="true">→</span>
    </a>`;
}

function renderLoading(listbox) {
    listbox.innerHTML = `<div class="space-y-3 p-4" aria-live="polite" aria-label="Loading suggestions">
        ${[1, 2, 3].map(() => '<div class="flex animate-pulse items-center gap-3"><div class="h-10 w-10 rounded-lg bg-stone-200 dark:bg-stone-700"></div><div class="flex-1 space-y-2"><div class="h-3 w-2/3 rounded bg-stone-200 dark:bg-stone-700"></div><div class="h-2 w-1/2 rounded bg-stone-100 dark:bg-stone-800"></div></div></div>').join('')}
    </div>`;
}

function renderMessage(listbox, message) {
    listbox.innerHTML = `<div class="p-5 text-sm text-stone-600 dark:text-stone-300" role="status">${escapeHtml(message)}</div>`;
}

function renderRecent(listbox, container, input) {
    const recent = getRecentSearches();
    if (!recent.length) {
        listbox.innerHTML = '';
        return;
    }
    const removeLabel = container.dataset.searchRemoveRecent || 'Remove recent search';
    listbox.innerHTML = `<h2 class="border-b border-stone-100 bg-stone-50 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-stone-500 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-400">${escapeHtml(container.dataset.searchRecent || 'Recent searches')}</h2>` + recent.map((query, index) =>
        `<div class="flex items-stretch border-b border-stone-100 dark:border-stone-800">
            <button id="recent-search-${index}" type="button" role="option" aria-selected="false" data-search-option class="flex min-h-12 min-w-0 flex-1 items-center gap-3 px-4 py-3 text-left text-sm text-stone-700 hover:bg-stone-50 focus:bg-amber-50 focus:outline-none dark:text-stone-200 dark:hover:bg-stone-800 dark:focus:bg-amber-500/10"><span aria-hidden="true">↻</span><span class="truncate">${escapeHtml(query)}</span></button>
            <button type="button" data-remove-recent-search data-recent-query="${escapeHtml(query)}" aria-label="${escapeHtml(`${removeLabel}: ${query}`)}" class="inline-flex min-h-11 w-11 shrink-0 items-center justify-center text-stone-400 transition-colors hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-amber-400 dark:text-stone-500 dark:hover:text-red-400"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
        </div>`
    ).join('');
    listbox.querySelectorAll('button[data-search-option]').forEach((button, index) => {
        button.addEventListener('click', () => {
            input.value = recent[index];
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });
    listbox.querySelectorAll('[data-remove-recent-search]').forEach(button => {
        button.addEventListener('click', event => {
            event.stopPropagation();
            removeRecentSearch(button.dataset.recentQuery);
            renderRecent(listbox, container, input);
            input.dispatchEvent(new Event('search-options-updated'));
        });
    });
}

function rememberSearch(value) {
    const query = normalize(value);
    if (!query) return;
    const recent = [query, ...getRecentSearches().filter(item => item !== query)].slice(0, 5);
    localStorage.setItem(RECENT_KEY, JSON.stringify(recent));
}

function getRecentSearches() {
    try {
        return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]').filter(Boolean).slice(0, 5);
    } catch {
        return [];
    }
}

function removeRecentSearch(value) {
    const recent = getRecentSearches().filter(item => item !== value);
    localStorage.setItem(RECENT_KEY, JSON.stringify(recent));
}

function highlight(text, query) {
    const safeText = escapeHtml(text);
    const terms = normalize(query).split(' ').filter(term => term.length > 1);
    if (!terms.length) return safeText;
    const pattern = new RegExp(`(${terms.map(escapeRegExp).join('|')})`, 'gi');
    return safeText.replace(pattern, '<mark class="rounded bg-amber-100 px-0.5 text-inherit dark:bg-amber-500/20">$1</mark>');
}

function normalize(value) {
    return String(value || '').trim().replace(/\s+/g, ' ').toLowerCase();
}

function escapeRegExp(value) {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function escapeHtml(text) {
    return String(text ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    })[character]);
}

function safeUrl(value) {
    if (!value) return '';

    try {
        const url = new URL(value, window.location.origin);
        return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
    } catch {
        return '';
    }
}

function nextSearchId(prefix) {
    searchIdCounter += 1;
    return `${prefix}-${searchIdCounter}`;
}

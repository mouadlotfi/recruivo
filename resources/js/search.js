// Live search functionality with suggestions
document.addEventListener('DOMContentLoaded', function() {
    const searchInputs = document.querySelectorAll('.search-input');
    let debounceTimer;

    searchInputs.forEach(input => {
        const container = input.closest('.search-container');
        if (!container) return;

        // Create suggestions dropdown if it doesn't exist
        let suggestionsEl = container.querySelector('.search-suggestions');
        if (!suggestionsEl) {
            suggestionsEl = document.createElement('div');
            suggestionsEl.className = 'search-suggestions hidden absolute top-full left-1/2 -translate-x-1/2 mt-3 bg-white dark:bg-stone-800 rounded-2xl shadow-2xl border border-stone-200 dark:border-stone-700 max-h-[500px] overflow-hidden z-[9999]';
            container.appendChild(suggestionsEl);
            
            // Ensure container has relative positioning
            container.style.position = 'relative';
        }
        
        // Update dropdown width to be responsive - 3x the input width or max 700px on desktop, smaller on mobile
        const updateDropdownWidth = () => {
            const inputWidth = input.offsetWidth;
            const dropdownWidth = Math.min(inputWidth * 3, 700);
            suggestionsEl.style.width = window.innerWidth < 640 ? '85vw' : `${dropdownWidth}px`;
        };
        updateDropdownWidth();
        window.addEventListener('resize', updateDropdownWidth);

        input.addEventListener('input', function(e) {
            const query = e.target.value.trim();

            clearTimeout(debounceTimer);

            if (query.length < 1) {
                suggestionsEl.classList.add('hidden');
                return;
            }

            // Show dropdown immediately with loading state
            suggestionsEl.innerHTML = `
                <div class="p-4 sm:p-8">
                    <div class="text-center">
                        <div class="inline-block animate-spin rounded-full h-6 w-6 sm:h-8 sm:w-8 border-4 border-stone-200 border-t-amber-600 dark:border-stone-700 dark:border-t-amber-400"></div>
                    </div>
                </div>
            `;
            suggestionsEl.classList.remove('hidden');

            debounceTimer = setTimeout(() => {
                fetchSuggestions(query, suggestionsEl, input);
            }, 300);
        });

        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!container.contains(e.target)) {
                suggestionsEl.classList.add('hidden');
            }
        });

        // Close on escape key
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                suggestionsEl.classList.add('hidden');
            }
        });
    });
});

async function fetchSuggestions(query, suggestionsEl, input) {
    try {
        const response = await fetch(`/api/search/suggestions?q=${encodeURIComponent(query)}`);
        const results = await response.json();

        const noResultsText = document.documentElement.lang === 'fr' ? 'Aucun résultat trouvé' : 'No results found';

        if (results.length === 0) {
            suggestionsEl.innerHTML = `
                <div class="p-4 sm:p-6">
                    <div class="py-4 sm:py-8 text-center text-stone-500 dark:text-stone-400">
                        <svg class="mx-auto h-8 w-8 sm:h-12 sm:w-12 mb-2 sm:mb-3 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <p class="text-sm sm:text-base">${noResultsText}</p>
                    </div>
                </div>
            `;
            suggestionsEl.classList.remove('hidden');
            return;
        }

        const jobLabel = document.documentElement.lang === 'fr' ? 'Emploi' : 'Job';
        const companyLabel = document.documentElement.lang === 'fr' ? 'Entreprise' : 'Company';

        const resultsHtml = results.map(item => {
            const typeLabel = item.type === 'job' ? jobLabel : companyLabel;
            const typeBadgeClass = item.type === 'job' 
                ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300'
                : 'bg-teal-100 text-teal-700 dark:bg-teal-500/10 dark:text-teal-300';

            return `
                <a href="${item.url}" class="flex items-center gap-2 sm:gap-3 p-2 sm:p-3 hover:bg-stone-50 dark:hover:bg-stone-700/50 transition border-b border-stone-100 dark:border-stone-700 last:border-0">
                    ${item.logo ? `
                        <img src="${item.logo}" alt="${item.title}" class="w-8 h-8 sm:w-10 sm:h-10 rounded object-cover flex-shrink-0" />
                    ` : `
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-white text-sm sm:text-base font-semibold flex-shrink-0">
                            ${item.title.charAt(0).toUpperCase()}
                        </div>
                    `}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            <p class="text-sm sm:text-base font-medium text-stone-900 dark:text-stone-100 truncate">${escapeHtml(item.title)}</p>
                            <span class="px-1.5 sm:px-2 py-0.5 text-[10px] sm:text-xs font-medium rounded-full ${typeBadgeClass} flex-shrink-0">
                                ${typeLabel}
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400 truncate">${escapeHtml(item.subtitle)}</p>
                    </div>
                </a>
            `;
        }).join('');

        suggestionsEl.innerHTML = `
            <div class="max-h-[400px] sm:max-h-[500px] overflow-y-auto">
                ${resultsHtml}
            </div>
        `;

        suggestionsEl.classList.remove('hidden');
    } catch (error) {
        console.error('Error fetching suggestions:', error);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}


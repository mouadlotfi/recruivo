// Mobile search modal handling
document.addEventListener('DOMContentLoaded', function() {
    const searchToggle = document.getElementById('mobile-search-toggle');
    const searchModal = document.getElementById('mobile-search-modal');
    const searchClose = document.getElementById('mobile-search-close');
    const background = document.getElementById('main-content');
    let searchPreviousFocus = null;

    const closeSearch = () => {
        if (!searchModal || searchModal.classList.contains('hidden')) return;

        searchModal.querySelectorAll('.search-suggestions').forEach(suggestions => {
            suggestions.classList.add('hidden');
        });
        searchModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        background?.removeAttribute('inert');
        searchPreviousFocus?.focus();
    };
    

    if (searchToggle && searchModal) {
        searchToggle.addEventListener('click', function() {
            searchPreviousFocus = document.activeElement;
            searchModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            background?.setAttribute('inert', '');
            const input = searchModal.querySelector('input[type="search"]');
            if (input) requestAnimationFrame(() => input.focus());
        });
    }
    
    if (searchClose && searchModal) {
        searchClose.addEventListener('click', function() {
            closeSearch();
        });
        
        searchModal.addEventListener('click', function(e) {
            if (e.target === searchModal) {
                closeSearch();
            }
        });

        searchModal.addEventListener('keydown', function(e) {
            if (e.key !== 'Tab') return;

            const focusable = [...searchModal.querySelectorAll(
                'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            )].filter(element => element.getClientRects().length > 0);
            if (!focusable.length) return;

            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSearch();
        }
    });
});


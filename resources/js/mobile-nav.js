// Mobile navigation dropdown handling
document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggle = document.getElementById('mobile-dropdown-toggle');
    const dropdown = document.getElementById('mobile-dropdown');
    const searchToggle = document.getElementById('mobile-search-toggle');
    const searchModal = document.getElementById('mobile-search-modal');
    const searchClose = document.getElementById('mobile-search-close');
    
    if (dropdownToggle && dropdown) {
        dropdownToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
        });
        
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target) && !dropdownToggle.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    }
    
    if (searchToggle && searchModal) {
        searchToggle.addEventListener('click', function() {
            searchModal.classList.remove('hidden');
            const input = searchModal.querySelector('input[type="search"]');
            if (input) input.focus();
        });
    }
    
    if (searchClose && searchModal) {
        searchClose.addEventListener('click', function() {
            searchModal.classList.add('hidden');
        });
        
        searchModal.addEventListener('click', function(e) {
            if (e.target === searchModal) {
                searchModal.classList.add('hidden');
            }
        });
    }
});


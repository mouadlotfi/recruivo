document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            
            if (isDark) {
                html.classList.remove('dark');
                localStorage.setItem('recruivo:theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('recruivo:theme', 'dark');
            }
        });
    }
});


// Shared Theme Management for Purr'Coffee POS
// This file handles light/dark mode across all pages

// Initialize theme on page load
function initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeUI(savedTheme);
}

// Toggle between light and dark mode
function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeUI(newTheme);
}

// Update theme toggle button UI
function updateThemeUI(theme) {
    const icon = document.getElementById('themeIcon');
    const text = document.getElementById('themeText');
    
    if (icon && text) {
        if (theme === 'dark') {
            icon.setAttribute('data-lucide', 'sun');
            text.innerText = 'Light Mode';
        } else {
            icon.setAttribute('data-lucide', 'moon');
            text.innerText = 'Dark Mode';
        }
        
        // Reinitialize lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons({ attrs: { 'stroke-width': 2.5 } });
        }
    }
}

// Auto-initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTheme);
} else {
    initTheme();
}

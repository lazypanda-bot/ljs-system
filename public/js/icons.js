document.addEventListener('DOMContentLoaded', () => {
    const iconMap = {
        'fa-arrow-right': 'arrow-right',
        'fa-bath': 'bath',
        'fa-bed': 'bed-double',
        'fa-bell': 'bell',
        'fa-building': 'building-2',
        'fa-calendar-alt': 'calendar-days',
        'fa-calendar-check': 'calendar-check-2',
        'fa-car': 'car-front',
        'fa-chart-column': 'chart-column',
        'fa-chart-pie': 'chart-pie',
        'fa-check-circle': 'circle-check',
        'fa-circle-check': 'circle-check',
        'fa-chevron-down': 'chevron-down',
        'fa-chevron-left': 'chevron-left',
        'fa-chevron-right': 'chevron-right',
        'fa-circle': 'circle',
        'fa-comments': 'messages-square',
        'fa-envelope': 'mail',
        'fa-expand': 'expand',
        'fa-eye': 'eye',
        'fa-file-csv': 'file-spreadsheet',
        'fa-file-pdf': 'file-text',
        'fa-gear': 'settings',
        'fa-heart': 'heart',
        'fa-home': 'house',
        'fa-layer-group': 'layers-2',
        'fa-lock': 'lock-keyhole',
        'fa-magnifying-glass': 'search',
        'fa-map-marker-alt': 'map-pin',
        'fa-paperclip': 'paperclip',
        'fa-paper-plane': 'send',
        'fa-pen': 'pencil',
        'fa-phone-alt': 'phone',
        'fa-plus': 'plus',
        'fa-right-from-bracket': 'log-out',
        'fa-shield-alt': 'shield-check',
        'fa-star': 'star',
        'fa-tag': 'tag',
        'fa-trash': 'trash-2',
        'fa-trash-can': 'trash-2',
        'fa-user': 'user-round',
        'fa-user-plus': 'user-round-plus',
        'fa-users': 'users-round',
        'fa-user-tie': 'user-round-pen',
        'fa-vector-square': 'scan',
    };

    document.querySelectorAll('i[class*="fa-"]').forEach((element) => {
        const iconClass = [...element.classList].find((name) => iconMap[name]);
        if (!iconClass) return;

        const retainedClasses = [...element.classList].filter((name) => !name.startsWith('fa-') && !['fas', 'far', 'fab', 'fal', 'fad'].includes(name));
        element.removeAttribute('class');
        element.classList.add('lucide-icon', ...retainedClasses);
        element.setAttribute('data-lucide', iconMap[iconClass]);
        element.setAttribute('aria-hidden', element.getAttribute('aria-hidden') || 'true');
    });

    if (window.lucide?.createIcons) {
        window.lucide.createIcons({
            attrs: {
                'stroke-width': 2,
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
            },
        });
    }
});

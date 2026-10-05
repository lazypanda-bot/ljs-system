document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.staff-filter-bar input[type="search"]').forEach((input) => {
        let timer;
        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => input.form.submit(), 450);
        });

        if (input.value && new URLSearchParams(location.search).has(input.name)) {
            input.focus();
            input.setSelectionRange(input.value.length, input.value.length);
        }
    });
});

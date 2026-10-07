//ADDS PROPERTY WHEN SAVE PROPERTY BUTTON IS CLICKED IN PROPERTY CREATE
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-type-requirements]');
    if (!form) return;

    const requirements = JSON.parse(form.dataset.typeRequirements || '{}');
    const visibility = JSON.parse(form.dataset.typeVisibility || '{}');
    const detailFields = ['lot_area', 'floor_area', 'bedrooms', 'bathrooms'];
    const allFields = ['lot_area', 'floor_area', 'bedrooms', 'bathrooms', 'parking_spaces', 'number_of_floors', 'property_condition', 'year_built'];
    const labels = { lot_area: 'lot area', floor_area: 'floor area', bedrooms: 'bedrooms', bathrooms: 'bathrooms' };
    const hint = form.querySelector('[data-detail-hint]');

    const currentType = () => {
        const checked = form.querySelector('input[name="property_type"]:checked');
        return (checked?.value || '').trim().toLowerCase();
    };

    const applyRequirements = () => {
        const type = currentType();
        // Unknown or custom types keep every field visible.
        const visible = visibility[type] || allFields;

        allFields.forEach((name) => {
            const input = form.querySelector(`[name="${name}"]`);
            const label = input?.closest('label');
            if (!input || !label) return;
            const show = visible.includes(name);
            label.hidden = !show;
            input.disabled = !show;
        });

        // FORCE ALL DETAIL FIELDS TO BE OPTIONAL (Not required)
        detailFields.forEach((name) => {
            const input = form.querySelector(`[name="${name}"]`);
            if (!input) return;
            input.required = false; // <--- Changed from dynamic requirement to false
            const mark = input.closest('label')?.querySelector('.detail-required-mark');
            if (mark) mark.hidden = true; // <--- Hides the required asterisk marks
        });

        if (hint) {
            hint.textContent = !type
                ? 'Choose a property type. All property details are optional.'
                : 'All property details are optional for this property type.';
        }
    };

    const addTypeButton = document.getElementById('addTypeChoice');
    const customTypeInput = document.getElementById('customType');
    const addCustomType = () => {
        const label = customTypeInput.value.trim();
        if (!label) return;
        const list = document.getElementById('typeChoices');
        let radio = Array.from(list.querySelectorAll('input[type="radio"]'))
            .find((item) => item.value.toLowerCase() === label.toLowerCase());

        if (!radio) {
            const choice = document.createElement('label');
            choice.className = 'property-choice is-custom';
            radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = 'property_type';
            radio.value = label;
            radio.required = true;
            const text = document.createElement('span');
            text.textContent = label;
            choice.append(radio, text);
            list.appendChild(choice);
        }

        radio.checked = true;
        customTypeInput.value = '';
        applyRequirements();
    };
    addTypeButton?.addEventListener('click', addCustomType);
    customTypeInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            addCustomType();
        }
    });

    form.addEventListener('change', (event) => {
        if (event.target.name === 'property_type') applyRequirements();
    });

    const addPropertyForm = document.getElementById('addPropertyForm') || form;
    addPropertyForm.addEventListener('submit', function (event) {
        const fileInput = document.getElementById('propertyImages');
        if (fileInput && fileInput.files.length === 0) {
            // Uncomment if you want to enforce at least one image
            // event.preventDefault();
            // alert('Please select at least one image for the property.');
        }
    });

    applyRequirements();
});
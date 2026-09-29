document.addEventListener('DOMContentLoaded', function () {
    const profilePhotoInput = document.querySelector('input[name="profile_photo"]');
    
    if (profilePhotoInput) {
        profilePhotoInput.addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (file) {
                // Optional: Add live preview logic if needed
                console.log('Selected profile photo:', file.name);
            }
        });
    }
});
document.addEventListener('DOMContentLoaded', () => {
    // Pagination button interactions
    const pageButtons = document.querySelectorAll('.page-btn');

    pageButtons.forEach(button => {
        button.addEventListener('click', function () {
            // Skip arrow controls for simple toggle demonstration
            if (this.querySelector('i')) return;

            pageButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
        });
    });
});

/**
 * Handles deleting a review entry from the list
 * @param {number} reviewId 
 */
function deleteReview(reviewId) {
    if (confirm('Are you sure you want to delete this review?')) {
        const reviewItem = document.querySelector(`.review-item[data-id="${reviewId}"]`);
        
        if (reviewItem) {
            // Optional: Smooth fade-out animation
            reviewItem.style.transition = 'all 0.3s ease';
            reviewItem.style.opacity = '0';
            reviewItem.style.transform = 'translateX(20px)';
            
            setTimeout(() => {
                reviewItem.remove();
                
                // Check if list is empty
                const remainingReviews = document.querySelectorAll('.review-item');
                if (remainingReviews.length === 0) {
                    const listContainer = document.getElementById('reviewsList');
                    listContainer.innerHTML = `<div style="padding: 40px; text-align: center; color: #94a3b8;">No reviews available.</div>`;
                }
            }, 300);
        }
    }
}
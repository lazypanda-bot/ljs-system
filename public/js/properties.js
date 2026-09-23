document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('.property-card').forEach(function (card) {
		card.addEventListener('click', function (event) {
			if (event.target.closest('button, a, select, input')) return;
			card.querySelector('.property-btn')?.click();
		});
	});
});

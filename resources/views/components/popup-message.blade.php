<div class="app-popup" id="appPopup" aria-hidden="true">
    <div class="app-popup-dialog" role="alertdialog" aria-modal="true" aria-labelledby="appPopupTitle" aria-describedby="appPopupMessage">
        <h3 id="appPopupTitle"></h3>
        <p id="appPopupMessage"></p>
        <button type="button" class="app-popup-ok" id="appPopupClose">OK</button>
    </div>
</div>
<script>
    (function () {
        const popup = document.getElementById('appPopup');
        const hide = () => { popup.classList.remove('open'); popup.setAttribute('aria-hidden', 'true'); };
        window.showPopup = function (title, message) {
            document.getElementById('appPopupTitle').textContent = title;
            document.getElementById('appPopupMessage').textContent = message;
            popup.classList.add('open');
            popup.setAttribute('aria-hidden', 'false');
            document.getElementById('appPopupClose').focus();
        };
        document.getElementById('appPopupClose').addEventListener('click', hide);
        popup.addEventListener('click', function (event) { if (event.target === popup) hide(); });
        document.addEventListener('keydown', function (event) { if (event.key === 'Escape') hide(); });
        @if (session('popup'))
            window.addEventListener('DOMContentLoaded', function () {
                window.showPopup(@json(session('popup.title')), @json(session('popup.message')));
            });
        @endif
    })();
</script>

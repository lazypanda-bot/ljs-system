# Stylesheet organization

- Edit a page or component's normal styles in its corresponding CSS file here, such as `auth-modal.css`, `dashboard.css`, or `client-profile.css`.
- Put website media queries and container queries in `responsive.css`. Its sections name the original stylesheet so related rules are easy to find.
- Keep the shared layout overrides at the end of `responsive.css`; their position preserves the existing cascade.
- Layouts load `responsive.css` after their component stylesheets. The embedded Messages page also loads it, with full-page overrides excluded where necessary.
- Website Blade templates use CSS classes instead of inline styles. Data-dependent report bar sizes are passed through `data-bar-size`; `reports.js` supplies the numeric CSS variable.
- Email styles stay in `resources/views/emails/` because email clients need self-contained styling.

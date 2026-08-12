# Parcel Delivery Management System

Native PHP, MySQL, HTML, CSS, JavaScript, and AJAX parcel delivery management system with admin and rider roles.

## Default logins
- Admin: admin@example.com / admin123
- Rider: rider@example.com / rider123

## Setup
1. Create a MySQL database named `parcel_delivery`.
2. Import `sql/schema.sql`.
3. Update database credentials in `config/config.php` if needed.
4. Place the project under your web server root.
5. Open `index.php` in the browser.

## Notes
- Google Maps autocomplete is wired in the UI and can be enabled by setting `GOOGLE_MAPS_API_KEY` in `config/config.php`.
- The tracking UI uses browser geolocation and AJAX polling.

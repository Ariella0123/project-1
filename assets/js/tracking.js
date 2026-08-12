(() => {
    const body = document.body;
    const role = body.dataset.userRole || 'guest';

    async function post(url, payload = {}) {
        return window.ParcelApp.request(url, payload);
    }

    function setStatusPill(active) {
        const pill = document.querySelector('[data-rider-status-pill]');
        const toggle = document.querySelector('[data-rider-status-toggle]');
        if (!pill || !toggle) {
            return;
        }
        pill.textContent = active ? 'Online' : 'Offline';
        pill.classList.toggle('status-online', active);
        pill.classList.toggle('status-offline', !active);
        toggle.textContent = active ? 'Go Offline' : 'Go Online';
        toggle.classList.toggle('primary-button', active);
        toggle.classList.toggle('secondary-button', !active);
        toggle.dataset.active = active ? '1' : '0';
        toggle.parentElement?.classList.toggle('active', active);
    }

    async function setOnlineStatus(active) {
        const payload = { status: active ? 'online' : 'offline' };
        const response = await post('api/rider_status.php', payload);
        if (response.ok) {
            setStatusPill(active);
        }
        return response;
    }

    async function sendLocation(position) {
        const payload = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
        };
        await post('api/location_update.php', payload);
    }

    function startTracking() {
        if (!navigator.geolocation) {
            return;
        }

        const poll = () => {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    sendLocation(position).catch(() => {});
                },
                () => {},
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        };

        poll();
        window.__parcelTrackingTimer = window.setInterval(poll, 15000);

        if (window.__parcelWatchId) {
            navigator.geolocation.clearWatch(window.__parcelWatchId);
        }
        window.__parcelWatchId = navigator.geolocation.watchPosition(
            (position) => sendLocation(position).catch(() => {}),
            () => {},
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 5000 }
        );
    }

    function stopTracking() {
        if (window.__parcelTrackingTimer) {
            window.clearInterval(window.__parcelTrackingTimer);
            window.__parcelTrackingTimer = null;
        }
        if (window.__parcelWatchId && navigator.geolocation) {
            navigator.geolocation.clearWatch(window.__parcelWatchId);
            window.__parcelWatchId = null;
        }
    }

    function bindRiderControls() {
        const toggle = document.querySelector('[data-rider-status-toggle]');
        if (!toggle) {
            return;
        }

        toggle.addEventListener('click', async () => {
            const active = toggle.dataset.active !== '1';
            const response = await setOnlineStatus(active);
            if (response.ok) {
                if (active) {
                    startTracking();
                } else {
                    stopTracking();
                }
            }
        });

        const isActive = toggle.dataset.active === '1';
        setStatusPill(isActive);
        if (isActive) {
            startTracking();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (role === 'rider') {
            bindRiderControls();
        }
    });
})();


(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const baseUrl = document.body.dataset.baseUrl || '';

    function resolveUrl(path) {
        if (path.startsWith('http://') || path.startsWith('https://')) {
            return path;
        }

        if (path.startsWith('/')) {
            return `${baseUrl}${path}`;
        }

        return `${baseUrl}/${path.replace(/^\//, '')}`;
    }

    async function request(url, data = {}, method = 'POST') {
        const formData = data instanceof FormData ? data : new FormData();
        if (!(data instanceof FormData)) {
            Object.entries(data).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    formData.append(key, value);
                }
            });
        }
        formData.append('csrf_token', csrfToken);

        const response = await fetch(resolveUrl(url), {
            method,
            body: formData,
            headers: { 'X-CSRF-Token': csrfToken },
            credentials: 'same-origin',
        });

        return response.json();
    }

    function showMessage(target, message, type = 'success') {
        if (!target) {
            return;
        }
        target.innerHTML = `<div class="alert ${type}">${message}</div>`;
    }

    function attachSearchFilter(inputSelector, rowSelector) {
        const input = document.querySelector(inputSelector);
        const rows = Array.from(document.querySelectorAll(rowSelector));
        if (!input || rows.length === 0) {
            return;
        }
        input.addEventListener('input', () => {
            const query = input.value.trim().toLowerCase();
            rows.forEach((row) => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    function initGoogleAutocomplete(options) {
        const settings = typeof options === 'string' ? { inputId: options } : (options || {});
        const input = document.getElementById(settings.inputId);
        if (!input) {
            return;
        }

        if (!window.google || !window.google.maps || !window.google.maps.places) {
            initAddressSearchFallback(input, settings);
            return;
        }

        const autocomplete = new google.maps.places.Autocomplete(input, {
            fields: ['formatted_address', 'geometry', 'name'],
            types: ['address'],
            componentRestrictions: { country: 'my' },
        });

        autocomplete.addListener('place_changed', () => {
            const place = autocomplete.getPlace();
            if (!place || !place.geometry || !place.geometry.location) {
                return;
            }

            const latitudeField = settings.latitudeId ? document.getElementById(settings.latitudeId) : null;
            const longitudeField = settings.longitudeId ? document.getElementById(settings.longitudeId) : null;
            if (latitudeField) {
                latitudeField.value = place.geometry.location.lat().toFixed(7);
            }
            if (longitudeField) {
                longitudeField.value = place.geometry.location.lng().toFixed(7);
            }

            if (place.formatted_address) {
                input.value = place.formatted_address;
            }
        });

        return autocomplete;
    }

    function initAddressSearchFallback(input, settings) {
        const wrapper = input.parentElement;
        if (!wrapper) {
            return;
        }

        wrapper.style.position = 'relative';

        const results = document.createElement('div');
        results.className = 'address_results';
        wrapper.appendChild(results);

        let timer = null;

        async function searchAddresses(query) {
            if (!query || query.trim().length < 3) {
                results.innerHTML = '';
                results.hidden = true;
                return;
            }

            const endpoint = `https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=5&q=${encodeURIComponent(query)}`;
            const response = await fetch(endpoint, { headers: { Accept: 'application/json' } });
            const places = await response.json();

            results.innerHTML = '';
            results.hidden = places.length === 0;

            places.forEach((place) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'address_result-item';
                const title = document.createElement('strong');
                title.textContent = place.display_name;
                const note = document.createElement('span');
                note.textContent = 'Tap to use this address';
                item.appendChild(title);
                item.appendChild(note);
                item.addEventListener('click', () => {
                    input.value = place.display_name;
                    const latitudeField = settings.latitudeId ? document.getElementById(settings.latitudeId) : null;
                    const longitudeField = settings.longitudeId ? document.getElementById(settings.longitudeId) : null;
                    if (latitudeField) {
                        latitudeField.value = Number(place.lat).toFixed(7);
                    }
                    if (longitudeField) {
                        longitudeField.value = Number(place.lon).toFixed(7);
                    }
                    results.innerHTML = '';
                    results.hidden = true;
                });
                results.appendChild(item);
            });
        }

        input.setAttribute('autocomplete', 'off');
        input.addEventListener('input', () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => {
                searchAddresses(input.value).catch(() => {
                    results.hidden = true;
                    results.innerHTML = '';
                });
            }, 300);
        });

        input.addEventListener('blur', () => {
            window.setTimeout(() => {
                results.hidden = true;
            }, 150);
        });

        input.addEventListener('focus', () => {
            if (results.children.length > 0) {
                results.hidden = false;
            }
        });
    }

    function initCameraPreview(inputSelector, previewSelector) {
        const input = document.querySelector(inputSelector);
        const preview = document.querySelector(previewSelector);
        if (!input || !preview) {
            return;
        }
        input.addEventListener('change', () => {
            const file = input.files && input.files[0];
            if (!file) {
                preview.removeAttribute('src');
                preview.hidden = true;
                return;
            }
            const reader = new FileReader();
            reader.onload = () => {
                preview.src = String(reader.result);
                preview.hidden = false;
            };
            reader.readAsDataURL(file);
        });
    }

    window.ParcelApp = {
        request,
        showMessage,
        attachSearchFilter,
        initGoogleAutocomplete,
        initCameraPreview,
        csrfToken,
        resolveUrl,
    };

    document.addEventListener('DOMContentLoaded', () => {
        attachSearchFilter('[data-search-input]', '[data-search-row]');
        initGoogleAutocomplete('customer_address');
        initCameraPreview('#delivery_photo', '#cameraPreview');
    });
})();

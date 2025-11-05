'use strict';

(function () {
    var settings = window.t3aManageNarration || {};

    var strings = settings.strings && typeof settings.strings === 'object'
        ? Object.assign({}, settings.strings)
        : {};

    var dateTimeFormat = settings.formats ? settings.formats.dateTime : '';

    var wpDate = window.wp && window.wp.date;

    function formatDate(isoString) {
        if (!isoString) {
            return '';
        }

        if (wpDate && typeof wpDate.dateI18n === 'function' && dateTimeFormat) {
            try {
                return wpDate.dateI18n(dateTimeFormat, isoString);
            } catch (e) {
                // Fallback to native Date formatting below.
            }
        }

        var parsed = new Date(isoString);

        if (isNaN(parsed.getTime())) {
            return '';
        }

        try {
            return parsed.toLocaleString();
        } catch (e) {
            return parsed.toISOString();
        }
    }

    function setStatus(statusEl, message, isDescription) {
        if (!statusEl) {
            return;
        }

        statusEl.textContent = message || '';
        statusEl.style.display = message ? '' : 'none';

        if (isDescription) {
            statusEl.classList.add('description');
        } else {
            statusEl.classList.remove('description');
        }
    }

    function setGenerated(generatedEl, label, formattedDate) {
        if (!generatedEl) {
            return;
        }

        if (formattedDate) {
            generatedEl.textContent = '';

            if (label) {
                var labelElement = document.createElement('strong');
                labelElement.textContent = label;

                generatedEl.appendChild(labelElement);
                generatedEl.appendChild(document.createElement('br'));
            }
            generatedEl.appendChild(document.createTextNode(formattedDate));

            generatedEl.style.display = '';
        } else {
            generatedEl.textContent = '';
            generatedEl.style.display = 'none';
        }
    }

    function setActionButton(button, label, manageUrl) {
        if (!button) {
            return;
        }

        button.textContent = label || '';

        if (typeof manageUrl === 'string' && manageUrl.length > 0) {
            button.setAttribute('href', manageUrl);
        }

        button.classList.add('button');
        button.classList.remove('button-primary');

        if (!button.classList.contains('button-secondary')) {
            button.classList.add('button-secondary');
        }
    }

    function normalizeBoolean(value) {
        if (typeof value === 'boolean') {
            return value;
        }

        if (typeof value === 'string') {
            return value.toLowerCase() === 'true';
        }

        if (typeof value === 'number') {
            return value !== 0;
        }

        return Boolean(value);
    }

    function resolveNarrationTypeCopy(typeValue) {
        if (typeof typeValue !== 'string' || !typeValue) {
            return '';
        }

        if (typeValue === 'human_narration') {
            return strings.narrationTypeHuman || 'Human narration';
        }

        if (typeValue === 'ai_narration') {
            return strings.narrationTypeAi || 'AI narration';
        }

        return typeValue.replace(/[_-]+/g, ' ');
    }

    function setNarrationType(typeEl, label, typeValue) {
        if (!typeEl) {
            return;
        }

        var typeText = resolveNarrationTypeCopy(typeValue);

        typeEl.textContent = '';
        typeEl.style.display = 'none';

        if (!typeText) {
            return;
        }

        if (label) {
            var labelElement = document.createElement('strong');
            labelElement.textContent = label;
            typeEl.appendChild(labelElement);
            typeEl.appendChild(document.createElement('br'));
        }

        typeEl.appendChild(document.createTextNode(typeText));
        typeEl.style.display = '';
    }

    function setStatusWithLabel(statusEl, label, message, isWarning) {
        if (!statusEl) {
            return;
        }

        statusEl.textContent = '';
        statusEl.style.display = '';
        statusEl.classList.remove('description');

        if (isWarning) {
            statusEl.style.color = '#856404';
            statusEl.style.backgroundColor = '#fff3cd';
            statusEl.style.padding = '8px 12px';
            statusEl.style.borderRadius = '4px';
        } else {
            statusEl.style.color = '';
            statusEl.style.backgroundColor = '';
            statusEl.style.padding = '';
            statusEl.style.borderRadius = '';
        }

        if (label) {
            var labelElement = document.createElement('strong');
            labelElement.textContent = label;
            statusEl.appendChild(labelElement);
            statusEl.appendChild(document.createElement('br'));
        }

        if (message) {
            statusEl.appendChild(document.createTextNode(message));
        }
    }

    function handleResponse(container) {
        var statusUrl = container.getAttribute('data-status-url');
        var manageUrl = container.getAttribute('data-manage-url');

        if (!statusUrl) {
            return;
        }

        var typeEl = container.querySelector('.js-t3a-manage-narration-type');
        var statusEl = container.querySelector('.js-t3a-manage-narration-status');
        var generatedEl = container.querySelector('.js-t3a-manage-narration-generated');
        var button = container.querySelector('.js-t3a-manage-narration-action a');
        var loginEl = container.querySelector('.js-t3a-manage-narration-login');

        setNarrationType(typeEl, strings.narrationTypeLabel, '');
        setStatus(statusEl, strings.loading, false);
        setGenerated(generatedEl, strings.lastGeneratedLabel, '');

        if (button) {
            button.style.display = 'none';
            button.removeAttribute('data-hidden');
            button.classList.remove('button-primary');
            button.classList.remove('button-secondary');
            button.textContent = '';
        }

        if (loginEl) {
            loginEl.style.display = 'none';
        }

        fetch(statusUrl, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json'
            }
        })
            .then(function (response) {
                if (response.status === 404) {
                    setGenerated(generatedEl, strings.lastGeneratedLabel, '');
                    setNarrationType(typeEl, strings.narrationTypeLabel, '');
                    setStatus(statusEl, strings.notFound, false);

                    if (button) {
                        button.style.display = 'none';
                        button.setAttribute('data-hidden', 'true');
                    }

                    if (loginEl) {
                        loginEl.style.display = 'none';
                    }

                    return null;
                }

                if (!response.ok) {
                    var httpError = new Error('http_error');
                    httpError.name = 'http_error';
                    throw httpError;
                }

                return response.json()
                    .catch(function () {
                        var invalid = new Error('invalid_json');
                        invalid.name = 'invalid_json';
                        throw invalid;
                    });
            })
            .then(function (data) {
                if (!data) {
                    return;
                }

                if (typeof data !== 'object' || Array.isArray(data)) {
                    var invalid = new Error('invalid_json');
                    invalid.name = 'invalid_json';
                    throw invalid;
                }

                var createdAt = typeof data.created_at === 'string' ? data.created_at : '';
                var narrationType = typeof data.type === 'string' ? data.type : '';
                var generatedLabel = strings.lastGeneratedLabel;
                var isPublished = false;

                if (narrationType === 'human_narration') {
                    generatedLabel = strings.uploadedLabel || strings.lastGeneratedLabel;
                }

                setNarrationType(typeEl, strings.narrationTypeLabel, narrationType);

                if (Object.prototype.hasOwnProperty.call(data, 'published_to_podcast')) {
                    isPublished = normalizeBoolean(data.published_to_podcast);
                } else if (Object.prototype.hasOwnProperty.call(data, 'published_to_podcast_feed')) {
                    isPublished = normalizeBoolean(data.published_to_podcast_feed);
                }

                if (createdAt) {
                    var formatted = formatDate(createdAt);
                    setGenerated(generatedEl, generatedLabel, formatted);
                } else {
                    setGenerated(generatedEl, generatedLabel, '');
                }

                if (isPublished) {
                    setStatusWithLabel(
                        statusEl,
                        strings.podcastStatusLabel,
                        strings.podcastPublished,
                        false
                    );
                } else {
                    setStatusWithLabel(
                        statusEl,
                        strings.podcastStatusLabel,
                        strings.podcastNotPublished,
                        true
                    );
                }

                setActionButton(button, strings.manageButton, manageUrl);
                if (button) {
                    button.style.display = '';
                    button.removeAttribute('data-hidden');

                    var loginElement = container.querySelector('.js-t3a-manage-narration-login');
                    if (loginElement) {
                        loginElement.setAttribute('style', 'color: #999; font-size: 11px; margin: 0; display: flex; align-items: center;');
                    }
                }
            })
            .catch(function (error) {
                var message = strings.unavailable;

                if (error && error.name === 'invalid_json') {
                    message = strings.unexpected;
                }

                setStatus(statusEl, message, true);
                setGenerated(generatedEl, strings.lastGeneratedLabel, '');
                setNarrationType(typeEl, strings.narrationTypeLabel, '');
                if (button) {
                    button.style.display = 'none';
                    button.setAttribute('data-hidden', 'true');
                }

                if (loginEl) {
                    loginEl.style.display = 'none';
                }
            });
    }

    function bootstrap() {
        var containers = document.querySelectorAll('.js-t3a-manage-narration');
        var index;

        if (!containers || !containers.length) {
            return;
        }

        for (index = 0; index < containers.length; index += 1) {
            handleResponse(containers[index]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrap);
    } else {
        bootstrap();
    }
})();

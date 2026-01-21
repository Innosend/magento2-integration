/**
 * @package     Innosend_Integration
 * @author      Henk Valk (henk@falconmedia.nl)
 * @version     1.0.0
 * @date        2025-11-28
 *
 * Copyright (c) 2025 Falcon Media (www.falconmedia.nl)
 */

/**
 * @api
 */
define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'jquery/ui'
], function ($, alert) {
    'use strict';

    $.widget('mage.testConnection', {
        options: {
            url: '',
            elementId: '',
            successText: '',
            failedText: '',
            fieldMapping: ''
        },

        /**
         * Bind handlers to events
         */
        _create: function () {
            this._on({
                'click': $.proxy(this._connect, this)
            });
        },

        /**
         * Format debug information for display
         * @private
         */
        _formatDebugInfo: function (debug) {
            if (!debug) {
                return '';
            }

            var debugHtml = '<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">';
            debugHtml += '<strong>Debug Information:</strong><br><br>';

            // Show masked values (api_key and api_secret are already masked by the controller)
            // The _full versions are for internal use only and not displayed
            if (debug.api_key) {
                debugHtml += '<strong>API Key:</strong> ' + this._escapeHtml(debug.api_key) + '<br>';
            }
            if (debug.api_secret) {
                debugHtml += '<strong>API Secret:</strong> ' + this._escapeHtml(debug.api_secret) + '<br>';
            }
            if (debug.endpoint) {
                debugHtml += '<strong>Endpoint:</strong> ' + this._escapeHtml(debug.endpoint) + '<br>';
            }
            if (debug.mode) {
                debugHtml += '<strong>Mode:</strong> ' + this._escapeHtml(debug.mode) + '<br>';
            }
            if (debug.timeout) {
                debugHtml += '<strong>Timeout:</strong> ' + debug.timeout + 's<br>';
            }
            if (debug.tested_url) {
                debugHtml += '<strong>Tested URL:</strong> ' + this._escapeHtml(debug.tested_url) + '<br>';
            }
            if (debug.http_code) {
                debugHtml += '<strong>HTTP Code:</strong> ' + debug.http_code + '<br>';
            }
            if (debug.last_http_code) {
                debugHtml += '<strong>Last HTTP Code:</strong> ' + debug.last_http_code + '<br>';
            }
            if (debug.last_error) {
                debugHtml += '<strong>Last Error:</strong> ' + this._escapeHtml(debug.last_error) + '<br>';
            }
            if (debug.tested_urls && Array.isArray(debug.tested_urls)) {
                debugHtml += '<strong>Tested URLs:</strong><br>';
                debug.tested_urls.forEach(function(url) {
                    debugHtml += '&nbsp;&nbsp;- ' + self._escapeHtml(url) + '<br>';
                });
            }
            if (debug.response) {
                debugHtml += '<strong>Response:</strong><br>';
                debugHtml += '<pre style="background: #f5f5f5; padding: 10px; overflow: auto; max-height: 200px;">' +
                    this._escapeHtml(debug.response) + '</pre>';
            }

            debugHtml += '</div>';
            return debugHtml;
        },

        /**
         * Escape HTML to prevent XSS
         * @private
         */
        _escapeHtml: function (text) {
            if (!text) {
                return '';
            }
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        /**
         * Method triggers an AJAX request to check API connection
         * @private
         */
        _connect: function () {
            var result = this.options.failedText,
                element = $('#' + this.options.elementId),
                self = this,
                params = {},
                msg = '',
                fieldToCheck = this.options.fieldToCheck || 'success';

            element.removeClass('success').addClass('fail');
            $.each(JSON.parse(this.options.fieldMapping), function (key, el) {
                var fieldValue = $('#' + el).val();
                if (fieldValue !== undefined && fieldValue !== null) {
                    params[key] = fieldValue;
                }
            });

            $.ajax({
                url: this.options.url,
                showLoader: true,
                data: params,
                type: 'POST',
                headers: this.options.headers || {}
            }).done(function (response) {
                var debugInfo = '';
                if (response.debug) {
                    debugInfo = self._formatDebugInfo(response.debug);
                }

                if (response[fieldToCheck]) {
                    element.removeClass('fail').addClass('success');
                    result = self.options.successText;
                    if (response.message) {
                        msg = response.message;
                    }
                    if (debugInfo) {
                        msg = (msg ? msg + '<br><br>' : '') + debugInfo;
                    }
                    if (msg) {
                        alert({
                            content: msg,
                            modalClass: 'innosend-test-connection-modal'
                        });
                    }
                } else {
                    msg = response.errorMessage || self.options.failedText;
                    if (debugInfo) {
                        msg = msg + debugInfo;
                    }

                    if (msg) {
                        alert({
                            content: msg,
                            modalClass: 'innosend-test-connection-modal'
                        });
                    }
                }
            }).fail(function (xhr) {
                msg = 'Request failed. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.errorMessage) {
                    msg = xhr.responseJSON.errorMessage;
                }
                if (xhr.responseJSON && xhr.responseJSON.debug) {
                    msg = msg + self._formatDebugInfo(xhr.responseJSON.debug);
                }
                alert({
                    content: msg,
                    modalClass: 'innosend-test-connection-modal'
                });
            }).always(function () {
                $('#' + self.options.elementId + '_result').text(result);
            });
        }
    });

    return $.mage.testConnection;
});


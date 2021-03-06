/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define('globalNavigation', [
    'jquery',
    'jquery/ui',
    'jquery/hover-intent'
], function ($) {
    'use strict';

    $.widget('mage.globalNavigation', {
        options: {
            menuCategory: '.level-0',
            menuLinks: 'a',
            itemsConfig: null,
            hoverIntentConfig: {
                interval: 100,
                timeout: 500 // number = milliseconds delay before onMouseOut
            },
            categoriesConfig: {
                '[data-ui-id="menu-mage-adminhtml-system"]': {
                    open: 'click'
                },
                '[data-ui-id="menu-mage-adminhtml-stores"]': {
                    open: 'click'
                }
            }
        },

        _create: function () {
            this.menu = this.element;
            this.menuCategory = $(this.options.menuCategory, this.menu);
            this.menuLinks = $(this.options.menuLinks, this.menuCategory);
            this._bind();
        },

        _menuCategoryBind: function (category, config) {
            category
                .hoverIntent($.extend({}, this.options.hoverIntentConfig, {
                    over: !config.open ? this._hoverEffects : $.noop,
                    out: !config.close ? this._leaveEffects : $.noop
                }));
            
            if (config.open) {
                category.on(config.open, this._hoverEffects);
            }

            if (config.close) {
                category.on(config.close, this._leaveEffects);
            }
        },

        _menuCategoryEvents: function () {
            this.menuCategory.each($.proxy(function (i, category) {
                var itemConfig = {};
                if (this.options.categoriesConfig) {
                    $.each(this.options.categoriesConfig, $.proxy(function (selector, conf) {
                        if ($(category).is(selector)) {
                            itemConfig = conf;
                        }
                    }, this));
                }
                this._menuCategoryBind($(category), itemConfig);
            }, this));
        },

        _bind: function () {
            this._menuCategoryEvents();
            this.menuLinks
                .on('focus.tabFocus', function (e) {
                    $(e.target).trigger('mouseenter');
                })
                .on('blur.tabFocus', function (e) {
                    $(e.target).trigger('mouseleave');
                });
        },

        _hoverEffects: function (e) {
            $(this)
                .addClass('hover recent')
                .siblings('.level-0').each(function () {
                    clearTimeout($(this).prop('hoverIntent_t'));
                    $(this).prop('hoverIntent_s', 0);
                    $(this).removeClass('recent hover');
                });

            var targetSubmenu = $(e.target).closest('.submenu');
            if (targetSubmenu.length && targetSubmenu.is(':visible')) {
                return;
            }
            var availableWidth = parseInt($(this).parent().css('width')) - $(this).position().left,
                submenu = $('> .submenu', this),
                colsWidth = 0;

            submenu.show();

            $.each($('> .submenu > ul li.column', this), function () {
                colsWidth = colsWidth + parseInt($(this).css('width'));
            });

            var containerPaddings = parseInt(submenu.css('padding-left')) + parseInt(submenu.css('padding-right'));

            $(this).toggleClass('reverse', (containerPaddings + colsWidth) > availableWidth);

            submenu
                .hide()
                .slideDown('fast');
        },

        _leaveEffects: function (e) {
            var targetSubmenu = $(e.target).closest('.submenu'),
                self = $(this),
                submenu = $('> .submenu', this);

            if (targetSubmenu.length && targetSubmenu.is(':hidden')) {
                return;
            }

            if (submenu.length) {
                submenu.slideUp('fast', function () {
                    self.removeClass('hover');
                });
            } else {
                self.removeClass('hover');
            }

        }
    });

    return $.mage.globalNavigation;
});

define('globalSearch', [
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    $.widget('mage.globalSearch', {
        options: {
            field: '.search-global-field',
            fieldActiveClass: 'active',
            input: '#search-global'
        },

        _create: function () {
            this.field = $(this.options.field);
            this.input = $(this.options.input);
            this._events();
        },

        _events: function () {
            var self = this;

            this.input
                .on('blur.resetGlobalSearchForm', function () {
                    if (!self.input.val()) {
                        self.field.removeClass(self.options.fieldActiveClass)
                    }
                });

            this.input
                .on('focus.activateGlobalSearchForm', function () {
                    self.field.addClass(self.options.fieldActiveClass)
                });
        }
    });

    return $.mage.globalSearch;
});

define('modalPopup', [
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    $.widget('mage.modalPopup', {
        options: {
            popup: '.popup',
            btnDismiss: '[data-dismiss="popup"]',
            btnHide: '[data-hide="popup"]'
        },

        _create: function () {
            this.fade = this.element;
            this.popup = $(this.options.popup, this.fade);
            this.btnDismiss = $(this.options.btnDismiss, this.popup);
            this.btnHide = $(this.options.btnHide, this.popup);

            this._events();
        },

        _events: function () {
            var self = this;

            this.btnDismiss
                .on('click.dismissModalPopup', function () {
                    self.fade.remove();
                });

            this.btnHide
                .on('click.hideModalPopup', function () {
                    self.fade.hide();
                });
        }
    });

    return $.mage.modalPopup;
});

define('useDefault', [
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    $.widget('mage.useDefault', {
        options: {
            field: '.field',
            useDefault: '.use-default',
            checkbox: '.use-default-control',
            label: '.use-default-label'
        },

        _create: function () {
            this.el = this.element;
            this.field = $(this.el).closest(this.options.field);
            this.useDefault = $(this.options.useDefault, this.field);
            this.checkbox = $(this.options.checkbox, this.useDefault);
            this.label = $(this.options.label, this.useDefault);
            this.origValue = this.el.attr('data-store-label');

            this._events();
        },

        _events: function () {
            var self = this;

            this.el
                .on('change.toggleUseDefaultVisibility keyup.toggleUseDefaultVisibility', $.proxy(this._toggleUseDefaultVisibility, this))
                .trigger('change.toggleUseDefaultVisibility');

            this.checkbox
                .on('change.setOrigValue', function () {
                    if ($(this).prop('checked')) {
                        self.el
                            .val(self.origValue)
                            .trigger('change.toggleUseDefaultVisibility');

                        $(this).prop('checked', false);
                    }
                });
        },

        _toggleUseDefaultVisibility: function () {
            var curValue = this.el.val(),
                origValue = this.origValue;

            this[curValue != origValue ? '_show' : '_hide']();
        },

        _show: function () {
            this.useDefault.show();
        },

        _hide: function () {
            this.useDefault.hide();
        }
    });

    return $.mage.useDefault;
});

define('loadingPopup', [
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    $.widget('mage.loadingPopup', {
        options: {
            message: 'Please wait...',
            timeout: 5000,
            timeoutId: null,
            callback: null,
            template: null
        },

        _create: function () {
            this.template =
                '<div class="popup popup-loading">' +
                '<div class="popup-inner">' + this.options.message + '</div>' +
                '</div>';

            this.popup = $(this.template);

            this._show();
            this._events();
        },

        _events: function () {
            var self = this;

            this.element
                .on('showLoadingPopup', function () {
                    self._show();
                })
                .on('hideLoadingPopup', function () {
                    self._hide();
                });
        },

        _show: function () {
            var options = this.options,
                timeout = options.timeout;

            $('body').trigger('processStart');

            if (timeout) {
                options.timeoutId = setTimeout(this._delayedHide.bind(this), timeout);
            }
        },

        _hide: function () {
            $('body').trigger('processStop');
        },

        _delayedHide: function () {
            this._hide();

            this.options.callback && this.options.callback();

            this.options.timeoutId && clearTimeout(this.options.timeoutId);
        }
    });

    return $.mage.loadingPopup;
});

define('collapsable', [
    'jquery',
    'jquery/ui',
    'jquery/jquery.tabs'
], function ($) {
    'use strict';

    $.widget('mage.collapsable', {
        options: {
            parent: null,
            openedClass: 'opened',
            wrapper: '.fieldset-wrapper'
        },

        _create: function () {
            this._events();
        },

        _events: function () {
            var self = this;

            this.element
                .on('show', function (e) {
                    var fieldsetWrapper = $(this).closest(self.options.wrapper);

                    fieldsetWrapper.addClass(self.options.openedClass);
                    e.stopPropagation();
                })
                .on('hide', function (e) {
                    var fieldsetWrapper = $(this).closest(self.options.wrapper);

                    fieldsetWrapper.removeClass(self.options.openedClass);
                    e.stopPropagation();
                });
        }
    });

    return $.mage.collapsable;
});

define('js/theme', [
    'jquery',
    'mage/smart-keyboard-handler',
    'mage/ie-class-fixer',
    'collapsable',
    'domReady!'
], function ($, keyboardHandler) {
    'use strict';

    /* @TODO refactor collapsable as widget and avoid logic binding with such a general selectors */
    $('.collapse').collapsable();

    $.each($('.entry-edit'), function (i, entry) {
        $('.collapse:first', entry).filter(function () {
            return $(this).data('collapsed') !== true;
        }).collapse('show');
    });

    keyboardHandler.apply();
});

(function () {
  'use strict';

  const strings = window.PishgamnoorAdmin || {};

  function initMenuSeparator() {
    document.querySelectorAll('#toplevel_page_pishgamnoor .wp-submenu a').forEach((link) => {
      if (link.href && link.href.indexOf('pishgamnoor-separator') !== -1) {
        link.parentElement.classList.add('pn-menu-separator');
      }
    });
  }

  function initTabs() {
    const tabs = document.querySelectorAll('.pn-tab[data-pn-tab]');
    const panels = document.querySelectorAll('.pn-panel[data-pn-panel]');
    if (!tabs.length || !panels.length) return;

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        const target = tab.getAttribute('data-pn-tab');
        tabs.forEach((item) => item.classList.toggle('is-active', item === tab));
        panels.forEach((panel) => panel.classList.toggle('is-active', panel.getAttribute('data-pn-panel') === target));
      });
    });
  }

  function initColorPickers(scope) {
    if (!window.jQuery || !jQuery.fn || !jQuery.fn.wpColorPicker) return;
    jQuery(scope || document).find('.pn-color-field').each(function () {
      const input = jQuery(this);
      if (input.closest('.wp-picker-container').length) return;
      input.wpColorPicker();
    });
  }

  function initRepeaters() {
    document.querySelectorAll('.pn-add-row[data-pn-add]').forEach((button) => {
      button.addEventListener('click', () => {
        const type = button.getAttribute('data-pn-add');
        const template = document.getElementById('pn-' + type + '-template');
        const repeater = button.closest('.pn-repeater');
        const rows = repeater ? repeater.querySelector('.pn-rows') : null;
        if (!template || !rows) return;

        const fragment = template.content.cloneNode(true);
        rows.appendChild(fragment);
        initColorPickers(rows);
        const last = rows.lastElementChild;
        if (last) {
          last.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      });
    });

    document.addEventListener('click', (event) => {
      const remove = event.target.closest('.pn-remove-row');
      if (!remove) return;
      const row = remove.closest('.pn-row');
      if (row) {
        row.remove();
      }
    });
  }

  function initMediaPicker() {
    document.querySelectorAll('.pn-media-button[data-pn-media-target]').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        if (!window.wp || !wp.media) return;

        const targetId = button.getAttribute('data-pn-media-target');
        const target = document.getElementById(targetId);
        if (!target) return;

        const frame = wp.media({
          title: strings.mediaTitle || 'انتخاب تصویر',
          button: { text: strings.mediaButton || 'استفاده از این تصویر' },
          multiple: false,
          library: { type: 'image' },
        });

        frame.on('select', () => {
          const attachment = frame.state().get('selection').first().toJSON();
          target.value = attachment.url;
          const picker = button.closest('.pn-image-picker');
          const preview = picker ? picker.querySelector('.pn-image-preview') : null;
          if (preview) {
            preview.innerHTML = '<img src="' + attachment.url + '" alt="">';
          }
        });

        frame.open();
      });
    });
  }

  function copyText(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(text);
    }

    return new Promise((resolve, reject) => {
      const area = document.createElement('textarea');
      area.value = text;
      area.setAttribute('readonly', 'readonly');
      area.style.position = 'fixed';
      area.style.top = '-1000px';
      document.body.appendChild(area);
      area.select();
      try {
        document.execCommand('copy');
        resolve();
      } catch (error) {
        reject(error);
      } finally {
        document.body.removeChild(area);
      }
    });
  }

  function initCopyButtons() {
    document.querySelectorAll('.pn-copy[data-pn-copy]').forEach((button) => {
      button.addEventListener('click', () => {
        const text = button.getAttribute('data-pn-copy') || '';
        const original = button.textContent;

        copyText(text)
          .then(() => {
            button.textContent = strings.copied || 'کپی شد';
            window.setTimeout(() => { button.textContent = original; }, 1600);
          })
          .catch(() => {
            window.alert(strings.copyFailed || 'کپی نشد؛ لطفاً دستی انتخاب کنید.');
          });
      });
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    initMenuSeparator();
    initTabs();
    initColorPickers(document);
    initRepeaters();
    initMediaPicker();
    initCopyButtons();
  });
})();

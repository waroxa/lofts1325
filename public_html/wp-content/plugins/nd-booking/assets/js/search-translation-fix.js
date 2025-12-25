(function () {
  const placeholderStartPattern = /#TRP[^#>]*>|#!trpst#/gi;
  const placeholderEndPattern = /#TRPEN#|#!trpen#/gi;
  const orphanedMarkerPattern = /#TRP\w*#|#!trp\w*#/gi;
  const wrappedMarkupPattern = /#!trpst#trp-gettext[^#]*#!trpen#(.*?)#!trpst#\/trp-gettext#!trpen#/gis;

  const normalize = (text) => (typeof text === 'string' ? text.replace(/\s+/g, ' ').trim() : '');

  const cleanPlaceholderText = (text) => {
    if (typeof text !== 'string' || (text.indexOf('#TRP') === -1 && text.indexOf('#!trp') === -1)) {
      return text;
    }

    const stripped = text
      .replace(wrappedMarkupPattern, '$1')
      .replace(placeholderStartPattern, '')
      .replace(placeholderEndPattern, '')
      .replace(orphanedMarkerPattern, '')
      .replace(/\s+/g, ' ')
      .trim();

    return stripped;
  };

  const cleanTranslationPlaceholders = () => {
    const root = document.body || document.documentElement;

    if (!root) return;

    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
      acceptNode: (node) =>
        node.nodeValue &&
        (node.nodeValue.indexOf('#TRP') !== -1 || node.nodeValue.indexOf('#!trp') !== -1)
          ? NodeFilter.FILTER_ACCEPT
          : NodeFilter.FILTER_SKIP,
    });

    const nodesToUpdate = [];

    while (walker.nextNode()) {
      nodesToUpdate.push(walker.currentNode);
    }

    nodesToUpdate.forEach((node) => {
      const cleaned = cleanPlaceholderText(node.nodeValue);

      if (cleaned !== node.nodeValue) {
        node.nodeValue = cleaned;
      }
    });
  };

  const localizeScarcityNotices = () => {
    const lang = (document.documentElement && document.documentElement.lang) || '';
    const isFrench = lang.toLowerCase().startsWith('fr');

    if (!isFrench) {
      return;
    }

    const translations = {
      'last room available': 'Dernière chambre disponible',
      'last rooms available': 'Dernières chambres disponibles',
    };

    const walker = document.createTreeWalker(document.body || document.documentElement, NodeFilter.SHOW_TEXT, {
      acceptNode: (node) => {
        if (!node.nodeValue) return NodeFilter.FILTER_SKIP;

        const normalized = normalize(node.nodeValue).toLowerCase();
        return Object.keys(translations).some((needle) => normalized.includes(needle))
          ? NodeFilter.FILTER_ACCEPT
          : NodeFilter.FILTER_SKIP;
      },
    });

    const nodesToTranslate = [];

    while (walker.nextNode()) {
      nodesToTranslate.push(walker.currentNode);
    }

    nodesToTranslate.forEach((node) => {
      const normalized = normalize(node.nodeValue).toLowerCase();
      let replacement = node.nodeValue;

      Object.entries(translations).forEach(([needle, target]) => {
        if (normalized.includes(needle)) {
          replacement = target;
        }
      });

      if (replacement === node.nodeValue) {
        const holder = node.parentElement;

        if (holder) {
          holder.style.display = 'none';
        } else {
          node.nodeValue = '';
        }

        return;
      }

      node.nodeValue = replacement;
    });
  };

  const runFixes = () => {
    cleanTranslationPlaceholders();
    localizeScarcityNotices();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runFixes, { once: true });
  } else {
    runFixes();
  }
})();

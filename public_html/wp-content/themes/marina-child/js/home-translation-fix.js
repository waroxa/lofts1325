(function () {
  const placeholderStartPattern = /#TRP[^#>]*>|#!trpst#/gi;
  const placeholderEndPattern = /#TRPEN#|#!trpen#/gi;
  const orphanedMarkerPattern = /#TRP\w*#|#!trp\w*#/gi;
  const wrappedMarkupPattern = /#!trpst#trp-gettext[^#]*#!trpen#(.*?)#!trpst#\/trp-gettext#!trpen#/gis;

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

  const cleanNodeText = (root) => {
    if (!root) return;

    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
      acceptNode: (node) => (node.nodeValue && node.nodeValue.indexOf('#TRP') !== -1)
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

  const runCleanup = () => {
    cleanNodeText(document.body || document.documentElement);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runCleanup, { once: true });
  } else {
    runCleanup();
  }
})();

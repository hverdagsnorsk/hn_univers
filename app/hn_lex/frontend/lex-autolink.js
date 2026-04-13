(() => {

  const IGNORE_TAGS = new Set([
    'A','SCRIPT','STYLE','CODE','PRE','TEXTAREA','INPUT'
  ]);

  function shouldIgnore(node) {
    return (
      !node ||
      node.nodeType !== Node.TEXT_NODE ||
      !node.textContent.trim() ||
      IGNORE_TAGS.has(node.parentElement.tagName) ||
      node.parentElement.classList.contains('lex-word')
    );
  }

  function splitTextNode(node) {
    const text = node.textContent;
    const words = text.split(/(\s+|[.,!?;:()])/);

    if (words.length < 3) return;

    const frag = document.createDocumentFragment();

    words.forEach(part => {
      if (/^[A-Za-zÆØÅæøå\-]+$/.test(part)) {
        const span = document.createElement('span');
        span.className = 'lex-word';
        span.textContent = part;
        frag.appendChild(span);
      } else {
        frag.appendChild(document.createTextNode(part));
      }
    });

    node.replaceWith(frag);
  }

  function walk(root) {
    const walker = document.createTreeWalker(
      root,
      NodeFilter.SHOW_TEXT,
      null
    );

    const nodes = [];
    while (walker.nextNode()) {
      nodes.push(walker.currentNode);
    }

    nodes.forEach(n => {
      if (!shouldIgnore(n)) {
        splitTextNode(n);
      }
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    const container =
      document.querySelector('[data-lex-autolink]') ||
      document.querySelector('.reader') ||
      document.body;

    walk(container);
  });

})();

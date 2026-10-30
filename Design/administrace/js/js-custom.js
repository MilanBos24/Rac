/* STRÁNKA: /administrace/napoveda.php | FAQ vyhledávání a rozbalovací otázky */
/* RAC FAQ – standalone vanilla JS, no frontend Porto JS needed | 2026-10-30-FAQ-01 */
(function () {
  'use strict';
  function init() {
    var root = document.querySelector('.rac-help');
    if (!root) return;
    var input = root.querySelector('#helpSearch');
    var topics = Array.prototype.slice.call(root.querySelectorAll('.rac-help-topic'));
    var count = root.querySelector('#helpCount');
    var empty = root.querySelector('#helpEmpty');
    function normalize(s) {
      var v = String(s || '').toLocaleLowerCase('cs');
      return typeof v.normalize === 'function' ? v.normalize('NFD').replace(/[\u0300-\u036f]/g, '') : v;
    }
    function filter() {
      var words = normalize(input ? input.value.trim() : '').split(/\s+/).filter(Boolean);
      var shown = 0;
      topics.forEach(function (topic) {
        var haystack = normalize(topic.getAttribute('data-search'));
        var visible = words.every(function (word) { return haystack.indexOf(word) !== -1; });
        topic.hidden = !visible;
        if (visible) shown++;
      });
      if (count) count.textContent = 'Nalezené návody: ' + shown;
      if (empty) empty.hidden = shown !== 0;
    }
    topics.forEach(function (topic) {
      var toggle = topic.querySelector('.rac-help-toggle');
      if (!toggle) return;
      toggle.addEventListener('click', function () {
        var panel = document.getElementById(toggle.getAttribute('aria-controls'));
        if (!panel) return;
        var expanded = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!expanded));
        panel.hidden = expanded;
      });
    });
    if (input) {
      var search = new URLSearchParams(window.location.search).get('q');
      if (search) input.value = search;
      input.addEventListener('input', filter);
    }
    filter();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

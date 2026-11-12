(function() {

var root = this;

var transfersearch = {};

root.transfersearch = transfersearch;

// If `pattern` matches `str`, wrap each matching character
// in `opts.pre` and `opts.post`. If no match, return null
transfersearch.match = function(pattern, str, opts) {
  opts = opts || {};
  var pre = opts.pre || ''
    , post = opts.post || ''
    , compareString = transfersearch.normalize(str)
    , normPattern = transfersearch.normalize(pattern);
  if (normPattern === '') {
    return {rendered: str, score: 0};
  } else if (compareString.includes(normPattern)) {
    return {rendered: str.replace(pattern, pre + pattern + post), score: Infinity};
  } else {
    return null;
  }
};

transfersearch.normalize = function(str) {
  return str.toLowerCase().normalize("NFD").replace(/\p{Diacritic}/gu, "");
};

}());

var setupAdaptPluginTickettransferDropdown = new Function (
    "e",
    setupAdaptDropdown.toString()
        .replace("fuzzy.match(r,o,s)", "transfersearch.match(e.term,t.text,s)")
        .replace(/^function[^{]+{/i,"")  // remove everything up to and including the first curly bracket
        .replace(/}[^}]*$/i, "")  // remove last curly bracket and everything after<br>
);

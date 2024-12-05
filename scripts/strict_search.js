(function() {

var root = this;

var transfersearch = {};

root.transfersearch = transfersearch;

// If `pattern` matches `str`, wrap each matching character
// in `opts.pre` and `opts.post`. If no match, return null
transfersearch.match = function(pattern, str, opts) {
  var compareString = transfersearch.normalize(str);
  pattern = transfersearch.normalize(pattern).replace("&", "&amp;").replace(">", "&gt;").replace("<", "&lt;");
  if (pattern === '') {
    return {rendered: str, score: 0};
  } else if (compareString.includes(pattern)) {
    return {rendered: str, score: Infinity};
  } else {
    return null;
  }
};

transfersearch.normalize = function(str) {
  return str.toLowerCase().normalize("NFD").replace(/\p{Diacritic}/gu, "");
};

}());

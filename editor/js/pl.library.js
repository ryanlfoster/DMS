
function plIsset(variable){
	if(typeof(variable) != "undefined" && variable !== null)
		return true
	else 
		return false
}
	
/* Data cleanup and handling
 * ============================================= */
function pl_html_input( text ) {
	return jQuery.trim( pl_htmlEntities( pl_stripSlashes( text ) ) );
}

function pl_stripSlashes (str) {

  return (str + '').replace(/\\(.?)/g, function (s, n1) {
    switch (n1) {
    case '\\':
      return '\\';
    case '0':
      return '\u0000';
    case '':
      return '';
    default:
      return n1;
    }
  });
}

function pl_htmlEntities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}





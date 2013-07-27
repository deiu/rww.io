<?php
/* empty.php
 * empty RDF container
 *
 * $Id$
 */

// TODO: non-RDF/XML media types
header('Content-Type: application/rdf+xml');
?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"></rdf:RDF>
<?php
TAG(__FILE__, __LINE__, '$Id$');

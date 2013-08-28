.. image:: http://fcns.eu/rww-logo.png
  :alt: RWW.IO
    :align: right

RWW.IO
======

RWW.IO is a personal Linked Data store, intended to be used as a
backend service for your Linked Data applications, and it supports the
latest standards and recommendations: [RDF]_, [JSON-LD]_, [SPARQL1.1-Update]_,
[WebID]_.

All data stores (endpoints) interpret the HTTP request URI as the base
URI for RDF operations and the default-graph URI for SPARQL
operations. When using the service as a backend, you need to follow
two basic rules:

- Specify the media type of your request data with a Content-Type HTTP header.
- Specify your response type preference with an Accept HTTP header.


Supported HTTP request methods:
-------------------------------

- Read: GET, HEAD, OPTIONS
- Write: PUT, MKCOL, DELETE
- Append: POST
- Update:
    - JSON PATCH (application/json)
    - SPARQL POST (*/sparql-query)



Supported response types
------------------------

- Web (index.html, style.css, script.js)
- JSON (Accept */json)
- JSON-P (GET ?callback=)
- SPARQL JSON (GET/POST ?query=)
- RSS (Accept */rss+xml)
- Atom (Accept */atom+xml)


RDF media types (defaults to text/turtle):
----------------
- JSON: application/json
- NTriples: */rdf+nt, */nt
- RDF/XML: */rdf+xml
- RDFa: */html, */xhtml
- Turtle: */turtle, */rdf+n3, */n3



Currently, the UI is minimal, allowing you to edit RDF documents and
set ACL rules. Users can also upload a limited range of images (PNG,
JPG and GIF - for all your cat pictures, wee!).

In case users do not have a WebID, they can create one once they have
selected their personal data store (i.e. deiu.example.com).

This project is currently being developed entirely in my free time, so
please consider supporting it (see "Support and donations" below). Thank you!

RWW.IO is live at http://rww.io/

Installation 
============

Requirements:
-------------

It requires the Redland librdf for php (http://librdf.org/docs/php.html)

::

    sudo apt-get install php5-librdf librdf0 librdf0-dev raptor2-utils libraptor2-dev libraptor2-0
    
Getting the code:
-----------------
::

    git clone https://github.com/deiu/rww.io.git

Configuration:
--------------

- The contents of the ``www/`` dir should then be made available to your Apache server (check ``conf/common.conf``).

- Check the apache conf files and change paths to your own server (see ``conf/httpd.conf``).

- You need to create a default storage location for your users' personal data stores. 
  If you installed RWW.IO under /var/www/rww.io/, then you have to manually create the /data/ directory under that path (/var/www/rww.io/data/). Don't forget to make the /data/ directory writable by the web server user!

- You need to have an SSL cert file configured (see ``conf/ssl.conf``).

Documentation
=============

At this point, the only existing documentation is this file and the commented
code. Until proper documentation is available, do not hesitate to
contact me with questions.

The ``www/root``  dir contains the PHP scripts used to run the http://rwww.io/ welcome page.

The ``www/wildcard``  dir contains the PHP scripts used to run all the personal data stores. 
Don't forget to check contents of the ``.htacces``  file there.
 
You can test with curl, for instance : 
::
    curl -v -L -H 'Accept: text/turtle' http://A_USER.example.com/

License
=======
This project is Copyright (C) 2010 by Joe Presbrey <presbrey@mit.edu>, Andrei Sambra <asambra@mit.edu>,
and published under MIT license (see LICENSE file).


Support and donations
=====================

You can help with the costs of running the website at http://rww.io/
by donating through the following links:

- Bitcoin: https://coinbase.com/checkouts/ed957952a941abf15d50696973fa4b92
- Paypal: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YCG7HFRPTVD4A
- Flattr: https://flattr.com/thing/1748916/

Every bit of cash helps. Thank you! :-)

References
==========
.. [RDF] http://www.w3.org/RDF/
.. [JSON-LD] http://www.w3.org/TR/json-ld/
.. [SPARQL1.1-Update] http://www.w3.org/TR/sparql11-update/
.. [WebID] http://dvcs.w3.org/hg/WebID/raw-file/tip/spec/identity-respec.html

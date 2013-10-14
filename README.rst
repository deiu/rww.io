.. image:: http://fcns.eu/rww-logo.png
  :alt: RWW.IO
    :align: right

RWW.IO
======

RWW.IO is a personal Linked Data store, intended to be used as a backend service for your Linked Data applications, and it
supports the latest standards and recommendations: RDF, JSON-LD, SPARQL 1.1 Update, WebID.

All data stores (endpoints) interpret the HTTP request URI as the base URI for RDF operations and the default-graph URI for SPARQL operations. When using the service as a backend, you need to follwo two basic rules:

- Specify the media type of your request data with a Content-Type HTTP header.
- Specify your response type preference with an Accept HTTP header.


Supported request methods:
--------------------------

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



Currently, the UI is minimal, allowing you to edit RDF documents and set ACL rules. Users can also upload a limited range of images (PNG, JPG and GIF - for all your cat pictures, wee!).

In case users do not have a WebID, they can create one once they have selected their personal data store (i.e. deiu.example.com).

This project is currently being developed entirely in my free time, so please consider supporting it. Thank you!

RWW.IO is live at http://rww.io/

Installation 
============

::

    git clone https://github.com/deiu/rww.io.git


- Check the apache conf files and change paths to your own server

- Requires librdf for php

::

    sudo apt-get install php5-librdf librdf0 librdf0-dev raptor2-utils libraptor2-dev libraptor2-0
    

- You need to create a default storage location for your users' personal data stores. If you installed RWW.IO under /var/www/rww.io/, then you have to manually create the /data/ directory under that path (/var/www/rww.io/data/). Don't forget to make the /data/ directory writable by the web server user!

- If you run into this Apache issue: ``VirtualHost overlap on port 443, the first has precedence``, please open the file /etc/apache2/ports.conf and make sure the ``<IfModule mod_ssl.c>`` directive also contains ``NameVirtualHost *:443``


Documentation
=============

At this point, the only existing documentation is the commented code. Until proper documentation will be available, do not hesitate to contact me with questions.


License
=======
MIT (see LICENSE file)


Support and donations
=====================

You can help with the costs of running the website at http://rww.io/ by donating through the following links:

- Bitcoin: https://coinbase.com/checkouts/ed957952a941abf15d50696973fa4b92
- Paypal: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YCG7HFRPTVD4A
- Flattr: https://flattr.com/thing/1748916/

Every bit of cash helps. Thank you! :-)



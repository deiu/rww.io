.. image:: http://fcns.eu/rww-logo.png
  :alt: RWW.IO
    :align: right

RWW.IO is a personal Linked Data store, intended to be used as a backend service for your Linked Data applications. For this reason, the UI is minimal, allowing you to edit RDF documents and set ACL rules. Users can also upload a limited range of images (PNG, JPG and GIF - for all your cat pictures, wee!).

This project is currently being developed entirely in my free time, so please consider supporting it. Thank you!

RWW.IO is live at http://rww.io/

Installation 
------------

::

    git clone https://github.com/deiu/rww.io.git



- Check the apache conf files and change paths to your own server

- Requires librdf for php
::

    sudo apt-get install php5-librdf librdf0 librdf0-dev raptor2-utils libraptor2-dev libraptor2-0

- You need to create a the default storage locations for your users. If you installed RWW under /var/www/rww.io/, then you have to manually create the /data/ dir under that path (/var/www/rww.io/data/). Make sure the /data/ dir is writable by the web server user!


Documentation
-------------

At this point, the only existing documentation is the commented code. Until proper documentation will be available, do not hesitate to contact me with questions.


License
-------
MIT (see LICENSE file)






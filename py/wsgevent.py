# gunicorn-integration logging hack

def log_request(self):
    return
    log = self.server.log
    if log:
        if hasattr(log, "info"):
            log.info(self.format_request())
        else:
            log.write(self.format_request())

import gevent.pywsgi
gevent.pywsgi.WSGIHandler.log_request = log_request

from gevent.greenlet import Greenlet
from gevent.queue import Queue

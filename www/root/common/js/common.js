/* $Id$ */

HTTP = Class.create(Ajax.Request, {
  request: function(url) {
    this.url = url;
    this.method = this.options.method;
    var params = Object.isString(this.options.parameters) ?
          this.options.parameters :
          Object.toQueryString(this.options.parameters);

    if (params) {
      if (this.method == 'get')
        this.url += (this.url.include('?') ? '&' : '?') + params;
      else if (/Konqueror|Safari|KHTML/.test(navigator.userAgent))
        params += '&_=';
    }

    this.parameters = params.toQueryParams();

    try {
      var response = new Ajax.Response(this);
      if (this.options.onCreate) this.options.onCreate(response);
      Ajax.Responders.dispatch('onCreate', this, response);

      this.transport.open(this.method.toUpperCase(), this.url,
        this.options.asynchronous);

      if (this.options.asynchronous) this.respondToReadyState.bind(this).defer(1);

      this.transport.onreadystatechange = this.onStateChange.bind(this);
      this.setRequestHeaders();

      this.body = this.method == 'post' ? (this.options.postBody || params) : null;
      this.body = this.body || this.options.body || '';
      this.transport.send(this.body);

      /* Force Firefox to handle ready state 4 for synchronous requests */
      if (!this.options.asynchronous && this.transport.overrideMimeType)
        this.onStateChange();

    }
    catch (e) {
      this.dispatchException(e);
    }
  }
});

dirname = function(path) {
    return path.replace(/\\/g, '/').replace(/\/[^\/]*\/?$/, '');
}

basename = function(path) {
    if (path.substring(path.length - 1) == '/')
        path = path.substring(0, path.length - 1);

    var a = path.split('/');
    return a[a.length - 1];
}

newJS = function(url, callback){
    var script = document.createElement("script")
    script.async = true;
    script.type = "text/javascript";
    script.src = url;
    if (callback) {
        if (script.readyState) { // IE
            script.onreadystatechange = function() {
                if (script.readyState == "loaded" || script.readyState == "complete") {
                    script.onreadystatechange = null;
                    callback();
                }
            };
        } else { // others
            script.onload = function() {
                callback();
            };
        }
    }
    return script;
}

/** Cookies **/
function setCookie(name,value,days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
    // reload
    window.location.reload(true);
}

function readCookie(name) {
    var nameEQ = name+"=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function deleteCookie(name) {
    setCookie(name,"",-1);
}

function notify (message, cls) {
    if (message) {
        $('alertbody').update(message);
        if (cls)
            $('alertbody').addClassName(cls);
        $('alert').show();
    } else {
        $('alert').hide();
        $('alertbody').classNames().each(function(elt) {
            $('alertbody').removeClassName(elt);
        });
    }
}


/** Web ACLs **/
wac = {};
wac.get = function(request_path, path) {
    // reset the checkboxes
    $('wac-read').checked = false;
    $('wac-write').checked = false;
    $('wac-append').checked = false;
    $('wac-recursive').checked = false;
    $('wac-users').value = '';
    
    // remove trailing / from the file name we append after .meta
    console.log('Path='+path);
     
    var File = path;
    if (path.substring(path.length - 1) == '/')
        File = path.substring(0, path.length - 1);
  
    var metaBase = window.location.protocol+'//'+window.location.host+window.location.pathname;

    // if the resource in question is not the .meta file itself
    if (File.substr(0, 5) != '.meta') {
        if (File == '..') { // we need to use the parent dir name
            path = basename(window.location.pathname);
            var metaBase = window.location.protocol+'//'+window.location.host+dirname(window.location.pathname)+'/';
            var metaFile = '.meta.'+basename(window.location.pathname);
            var metaURI = metaBase+metaFile;
            var innerRef = window.location.pathname; // the resource as inner ref
            var requestPath = request_path;
            var dir = window.location.protocol+'//'+window.location.host+innerRef;
            // Remove preceeding / from path
            if (innerRef.substr(0, 1) == '/')
                innerRef = innerRef.substring(1);
            innerRef = metaURI+'#'+innerRef;
        } else if (File == '') { // root
            path = '/';
            var metaBase = window.location.protocol+'//'+window.location.host+'/';
            var metaFile = '.meta';
            var metaURI = metaBase+metaFile;
            var innerRef = metaBase; // the resource as inner ref
            var requestPath = request_path;
            var dir = window.location.protocol+'//'+window.location.host+request_path;
        } else {
            var metaFile = '.meta.'+File;
            var metaURI = metaBase+metaFile;
            var innerRef = path; // the resource as inner ref
            var dir = window.location.protocol+'//'+window.location.host+request_path+innerRef;
            var requestPath = request_path;
            // Remove preceeding / from path
            if (innerRef.substr(0, 1) == '/')
                innerRef = innerRef.substring(1);
            innerRef = metaURI+'#'+innerRef;
        }
    } else { // the resource IS the meta file
        var metaFile = File;
        var metaURI = metaBase+File;
        var innerRef = metaURI;
        var dir = innerRef;
    }
    // DEBUG 

    console.log('resource='+innerRef);
    console.log('metafile='+metaFile);
    console.log('RDFresource='+innerRef);
    console.log('metaBase='+metaBase);
    console.log('metaURI='+metaURI);

    // For quick access to those namespaces:
    var RDF = $rdf.Namespace("http://www.w3.org/1999/02/22-rdf-syntax-ns#");
    var WAC = $rdf.Namespace("http://www.w3.org/ns/auth/acl#");
    
    var graph = $rdf.graph();

    var resource = $rdf.sym(innerRef);
    var fetch = $rdf.fetcher(graph);

    fetch.nowOrWhenFetched(metaURI,undefined,function(){
        // permissions
        var perms = graph.each(resource, WAC('mode'));

        // reset the checkboxes
        $('wac-read').checked = false;
        $('wac-write').checked = false;
        
        // we need to know if the .meta file doesn't exist or it's empty, so we
        // can later add default rules
        if (perms.length > 0)
            $('wac-exists').value = '1';
        
        var i, n = perms.length, mode;
        for (i=0;i<n;i++) {
            var mode = perms[i];
            if (mode == '<http://www.w3.org/ns/auth/acl#Read>')
                $('wac-read').checked = true;               
            else if (mode == '<http://www.w3.org/ns/auth/acl#Write>')
                $('wac-write').checked = true;
            else if (mode == '<http://www.w3.org/ns/auth/acl#Append>')
                $('wac-append').checked = true;
        }
        
        // defaultForNew
        var defaultForNew = graph.each(resource, WAC('defaultForNew'));
        console.log('Rec-link='+defaultForNew.toString().replace(/\<(.*?)\>/g, "$1"));
        console.log('Resource='+dir);
        if (defaultForNew.toString().replace(/\<(.*?)\>/g, "$1") == dir)
            $('wac-recursive').checked = true;
            
        // users
        var users = graph.each(resource, WAC('agent'));
        // remove the < > signs from URIs
        $('wac-users').value=users.toString().replace(/\<(.*?)\>/g, "$1");
    });

    // set path value in the title
    $('wac-path').innerHTML=path;
    $('wac-reqpath').innerHTML=requestPath;
}
// load permissions and display WAC editor
wac.edit = function(request_path, path) {
    var isDir = false;
    var File = path;
    if (path.substring(path.length - 1) == '/') {
        // we have a dir -> remove the recursive option from the editor
        isDir = true;
        File = path.substring(0, path.length - 1);
    }
    var metaBase = window.location.protocol+'//'+window.location.host+window.location.pathname;
    // if the resource in question is not the .meta file itself
    if (File.substr(0, 5) != '.meta') {
        if (File == '..') { // we need to use the parent dir name
            var metaBase = window.location.protocol+'//'+window.location.host+dirname(window.location.pathname)+'/';
            var metaFile = '.meta.'+basename(window.location.pathname);
            var metaURI = metaBase+metaFile;
        } else if (File == '') { // root
            var metaBase = window.location.protocol+'//'+window.location.host+'/';
            var metaFile = '.meta';
            var metaURI = metaBase+metaFile;
        } else {
            var metaFile = '.meta.'+File;
            var metaURI = metaBase+metaFile;
        }
    } else { // the resource IS the meta file
        var metaURI = metaBase+File;
    }
    
    console.log('metaURI='+metaURI);
    new HTTP(metaURI, {
        method: 'get',
        requestHeaders: {'Content-Type': 'text/turtle'}, 
        onSuccess: function() {
            // display the editor
            wac.get(request_path, path);
            $('wac-editor').show();
            if (isDir)
                $('recursive').show();
            else
                $('recursive').hide();
        },
        onFailure: function(r) {
            var status = r.status.toString();
            if (status != '404') { 
                var msg = 'Access denied';
                console.log(msg);
                        
                notify(msg, 'error');
                window.setTimeout("notify()", 2000);
            } else {
                wac.get(request_path, path);
                $('wac-editor').show();
                if (isDir)
                    $('recursive').show();
                else
                    $('recursive').hide();
            }
        }
    });
}

// hide the editor
wac.hide = function() {
    $('wac-editor').hide();
}
// overwrite
wac.put = function(uri, data, refresh) {    
    new HTTP(uri, {
        method: 'put',
        body: data,
        requestHeaders: {'Content-Type': 'text/turtle'}, 
        onSuccess: function() {
            if (refresh == true)
                window.location.reload(true);
        },
        onFailure: function() {
            var msg = 'Access denied';
            console.log(msg);
                        
            notify(msg, 'error');
            window.setTimeout("notify()", 2000);
        }
    });
}
// append
wac.post = function(uri, data, refresh) {    
    new HTTP(uri, {
        method: 'post',
        body: data,
        contentType: 'text/turtle',
        onSuccess: function() {
            if (refresh == true)
                window.location.reload(true);
        },
        onFailure: function() {
            var msg = 'Access denied';
            console.log(msg);
                        
            notify(msg, 'error');
            window.setTimeout("notify()", 2000);
        }
    });
}

wac.save = function(elt) {
    var path = $('wac-path').innerHTML;
    var reqPath = $('wac-reqpath').innerHTML;
    var users = $('wac-users').value.split(",");
    var read = $('wac-read').checked;
    var write = $('wac-write').checked;
    var append = $('wac-append').checked;
    var recursive = $('wac-recursive').checked;
    var exists = $('wac-exists').value;
    var owner = $('wac-owner').value;

    // For quick access to those namespaces:
    var RDF = $rdf.Namespace("http://www.w3.org/1999/02/22-rdf-syntax-ns#");
    var WAC = $rdf.Namespace("http://www.w3.org/ns/auth/acl#");

/**** Domain specific meta ****/
    // If there is no .meta at the root level, we must create one!
    var rootMeta = window.location.protocol+'//'+window.location.host+'/.meta';
    var rootDir = window.location.protocol+'//'+window.location.host+'/';
    // check if we have a meta for domain control
    // DEBUG    
    console.log("rootMeta="+rootMeta);
    
    var gotRootMeta = false;
    new HTTP(rootMeta, {
        method: 'get',
        requestHeaders: {'Content-Type': 'text/turtle'}, 
        onSuccess: function() {
             gotRootMeta = true;
        }
    });

    if (gotRootMeta == false) {
        var ng = new $rdf.graph();
        // add default rules
        ng.add($rdf.sym(rootMeta),
                WAC('accessTo'),
                $rdf.sym(window.location.protocol+'//'+window.location.host+'/'));
        ng.add($rdf.sym(rootMeta),
                WAC('accessTo'),
                $rdf.sym(rootMeta));
        ng.add($rdf.sym(rootMeta),
                WAC('agent'),
                $rdf.sym(owner));        
        ng.add($rdf.sym(rootMeta),
                WAC('mode'),
                WAC('Read'));
        ng.add(ng.sym(rootMeta),
                WAC('mode'),
                WAC('Write'));
        // add read for all
        ng.add($rdf.sym(rootDir),
                WAC('accessTo'),
                $rdf.sym(window.location.protocol+'//'+window.location.host+'/'));
        ng.add($rdf.sym(rootDir),
                WAC('accessTo'),
                $rdf.sym(rootDir));
        ng.add($rdf.sym(rootDir),
                WAC('agentClass'),
                $rdf.sym('http://xmlns.com/foaf/0.1/Agent'));
        ng.add($rdf.sym(rootDir),
                WAC('defaultForNew'),
                $rdf.sym(window.location.protocol+'//'+window.location.host+'/'));
        ng.add($rdf.sym(rootDir),
                WAC('mode'),
                WAC('Read'));
        var rootMetaData = new $rdf.Serializer(ng).toN3(ng);
        wac.post(rootMeta, rootMetaData, false);
    }


/**** File specific meta ****/
    var metaBase = window.location.protocol+'//'+window.location.host+reqPath;

    // Remove preceeding / from path
    if (reqPath.substr(0, 1) == '/')
        reqPath = reqPath.substring(1);

    // remove trailing slash from meta file
    var File = path;
    if (path.substring(path.length - 1) == '/')
        File = path.substring(0, path.length - 1);
        
    // Build the full .meta path URI
    if (path == '/') { // we're at the root level
        var metaURI = metaBase+'.meta';
        var innerRef = metaBase;
    } else if (path.substr(0, 5) != '.meta') { // got a normal file
        if (path+'/' == reqPath) { // we need to use the parent dir name
            path = reqPath;
            var metaBase = window.location.protocol+'//'+window.location.host+dirname(window.location.pathname)+'/';
            var metaFile = '.meta.'+basename(window.location.pathname);
            var metaURI = metaBase+metaFile;
            var innerRef = '#'+path;
        } else {
            var metaURI = metaBase+'.meta.'+File;
            var innerRef = '#'+path;
        }
    } else { // got a .meta file
        var metaURI = metaBase+path;
        path = path;
        var innerRef = metaURI;
    }
    // DEBUG
    console.log('path='+path);
    console.log('reqPath='+reqPath);
    console.log('resource='+metaBase+path);
    console.log('metaBase='+metaBase);
    console.log('metaURI='+metaURI);

    // Create a new graph
    var graph = new $rdf.graph();

    // path
    graph.add(graph.sym(innerRef),
                WAC('accessTo'),
                graph.sym(metaBase+path));
                
    // add allowed users
    if ((users.length > 0) && (users[0].length > 0)) {
        var i, n = users.length, user;
        for (i=0;i<n;i++) {
            var user = users[i].replace(/\s+|\n|\r/g,'');
            graph.add(graph.sym(innerRef),
                WAC('agent'),
                graph.sym(user));
        }
    } else {
        graph.add(graph.sym(innerRef),
                WAC('agentClass'),
                graph.sym('http://xmlns.com/foaf/0.1/Agent'));
    }
    
    // add access modes
    if (read == true) {
        graph.add(graph.sym(innerRef),
            WAC('mode'),
            WAC('Read'));
    }
    if (write == true) {
        graph.add(graph.sym(innerRef),
            WAC('mode'),
            WAC('Write'));
    } else if (append == true) {
        graph.add(graph.sym(innerRef),
            WAC('mode'),
            WAC('Append'));
    }
    // add recursion
    if (recursive == true) {
        graph.add(graph.sym(innerRef),
                WAC('defaultForNew'),
                graph.sym(metaBase+path));
    }
    // create default rules for the .meta file itself if we create it for the
    // first time
    if (exists == '0') {
        // Add the #Default rule for this domain
        graph.add(graph.sym(metaURI),
                WAC('accessTo'),
                graph.sym(metaURI));
        graph.add(graph.sym(metaURI),
                WAC('accessTo'),
                graph.sym(metaBase+path));
        graph.add(graph.sym(metaURI),
                WAC('agent'),
                graph.sym(owner));
        graph.add(graph.sym(metaURI),
                WAC('mode'),
                WAC('Read'));
        graph.add(graph.sym(metaURI),
                WAC('mode'),
                WAC('Write'));

        // serialize
        var data = new $rdf.Serializer(graph).toN3(graph);
        console.log(data);
        // POST the new rules to the server .meta file
        wac.post(metaURI, data, true);
    } else {
        // copy rules from old meta
        var g = $rdf.graph();
        var fetch = $rdf.fetcher(g);
              
        fetch.nowOrWhenFetched(metaURI,undefined,function(){
            // add accessTo
            graph.add(graph.sym(metaURI),
                WAC('accessTo'),
                graph.sym(metaURI));
                
            // add agents
            var agents = g.each(g.sym(metaURI), WAC('agent'));

            if (agents.length > 0) {
                var i, n = agents.length;
                for (i=0;i<n;i++) {
                    var agent = agents[i]['uri'];
                    graph.add(graph.sym(metaURI),
                        WAC('agent'),
                        graph.sym(agent));
                }
            } else {
                graph.add(graph.sym(metaURI),
                        WAC('agentClass'),
                        graph.sym('http://xmlns.com/foaf/0.1/Agent'));
            }
            // add permissions
            var perms = g.each($rdf.sym(metaURI), WAC('mode'));
            if (perms.length > 0) {
                var i, n = perms.length;
                for (i=0;i<n;i++) {
                    var perm = perms[i]['uri'];
                    graph.add(graph.sym(metaURI),
                        WAC('mode'),
                        graph.sym(perm));
                }
            }

            // serialize
            var data = new $rdf.Serializer(graph).toN3(graph);
            // DEBUG
            console.log(data);
            // PUT the new rules to the server .meta file
            wac.put(metaURI, data, true);
        });
    
    }

    // hide the editor
    $('wac-editor').hide();
}

cloud = {};
cloud.append = function(path, data) {
    data = data || ''
    new HTTP(this.request_url+path, { 
        method: 'post',
        body: data,
        contentType: 'text/turtle',
        onSuccess: function() {
            window.location.reload();
        },
        onFailure: function() {
            var msg = 'Access denied';
            console.log(msg);
                        
            alert(msg, 'error');
            window.setTimeout("alert()", 2000);
        }
    });
}
cloud.get = function(path) {
    var lastContentType = $F('editorType');
    new HTTP(this.request_url+path, { method: 'get', evalJS: false, requestHeaders: {'Accept': lastContentType}, onSuccess: function(r) {
            $('editorpath').value = path;
            $('editorpath').enable();
            $('editorarea').value = r.responseText;
            $('editorarea').enable();
            var contentType = r.getResponseHeader('Content-Type');
            var editorTypes = $$('#editorType > option');
            for (var i = 0; i < editorTypes.length; i++) {
                var oneContentType = editorTypes[i].value;
                if (oneContentType == contentType || oneContentType == '') {
                    editorTypes[i].selected = true;
                }
            }
            $('editor').show();
        }, onFailure: function() {
            var msg = 'Access denied';
            console.log(msg);
                        
            notify(msg, 'error');
            window.setTimeout("notify()", 2000);
        }
    });
}
cloud.mkdir = function(path) {
    new HTTP(this.request_url+path, { 
        method: 'mkcol',
        onSuccess: function() {
            window.location.reload();
        },
        onFailure: function() {
            var msg = 'Access denied';
            console.log(msg);
                        
            alert(msg, 'error');
            window.setTimeout("alert()", 2000);
        }
    });
}
cloud.put = function(path, data, type) {
    if (!type) type = 'text/turtle';
    new HTTP(this.request_url+path, {
        method: 'put',
        body: data,
        requestHeaders: {'Content-Type': type},
        onSuccess: function() {
            window.location.reload();
        },
        onFailure: function() {
            var msg = 'Access denied';
            console.log(msg);
                        
            notify(msg, 'error');
            window.setTimeout("notify()", 2000);
        }
    });
}
cloud.rm = function(path) {
    // also removes the corresponding .meta file if it exists
    var url = this.request_url;
    console.log('url='+url+' / path='+path);
    new HTTP(url+path, {
        method: 'delete',
        onSuccess: function() {
            if (path.substr(0, 5) != '.meta') {
                // remove trailing slash
                if (path.substring(path.length - 1) == '/')
                    path = path.substring(0, path.length - 1);
                // remove the .meta file
                new HTTP(url+'.meta.'+path, { method: 'delete', onSuccess: function() {                
                        window.location.reload();
                    }, onFailure: function() {
                        // refresh anyway
                        window.location.reload(); 
                    }
                });
            } else {
                window.location.reload();
            }
        },
        onFailure: function() {
            var msg = 'Access denied';
            console.log(msg);

            notify(msg, 'error');
            window.setTimeout("notify()", 2000);
        }
    });
}
cloud.edit = function(path) {
    $('editorpath').value = '';
    $('editorpath').disable();
    $('editorarea').value = '';
    $('editorarea').disable();
    cloud.get(path);
}
cloud.save = function(elt) {
    var path = $('editorpath').value;
    var data = $('editorarea').value;
    var type = $F('editorType');
    cloud.put(path, data, type);
}

cloud.init = function(data) {
    var k; for (k in data) { this[k] = data[k]; }
    this.storage = {};
    try {
        if ('localStorage' in window && window['localStorage'] !== null)
            this.storage = window.localStorage;
    } catch(e){}
}
cloud.refresh = function() { window.location.reload(); }
cloud.remove = function(elt) {
    new Ajax.Request(this.request_base+'/json/'+elt, { method: 'delete' });
}
cloud.updateStatus = function() {
    if (Ajax.activeRequestCount > 0) {
        $('statusLoading').show();
        $('statusComplete').hide();
    } else {
        $('statusComplete').show();
        $('statusLoading').hide();
    }
}

Ajax.Responders.register({
    onCreate: cloud.updateStatus,
    onComplete: function(q, r, data) {
        cloud.updateStatus();
        var msg = '';
        var cls = q.success() ? 'info' : 'error';
        try {
            msg += data.status.toString()+' '+data.message;
        } catch (e) {
            msg += r.status.toString()+' '+r.statusText;
        }
        var method = q.method.toUpperCase();
        var triples = r.getHeader('Triples');
        if (triples != null) {
            msg = triples.toString()+' triple(s): '+msg;
        } else {
            if (method == 'GET') {
                msg = r.responseText.length.toString()+' byte(s): '+msg;
            } else {
                msg = q.body.length.toString()+' byte(s): '+msg;
            }
        }
        // DEBUG
        console.log(msg);
        notify(method+' '+msg, cls);
        window.setTimeout("notify()", 3000);
    },
});

cloud.facebookInit = function() {
    FB.init({appId: '119467988130777', status: false, cookie: false, xfbml: true});
    FB._login = FB.login;
    FB.login = function(cb, opts) {
        if (!opts) opts = {};
        opts['next'] = cloud.request_base + '/login?id=facebook&display=popup';
        return FB._login(cb, opts);
    }
};
window.fbAsyncInit = cloud.facebookInit;

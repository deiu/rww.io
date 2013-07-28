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
//    return path.replace(/\\/g,'/').replace( /.*\//, '' );
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

wac = {};
wac.get = function(request_path, path) {
    // remove trailing / from the file name we append after .meta
    var requestPath = request_path+path;
     
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
        } else {
            var metaFile = '.meta.'+File;
            var metaURI = metaBase+metaFile;
            var innerRef = window.location.pathname+path; // the resource as inner ref
        }
        // Remove preceeding / from path
        if (innerRef.substr(0, 1) == '/')
            innerRef = innerRef.substring(1);
        innerRef = metaURI+'#'+innerRef;
    } else { // the resource IS the meta file
        var metaFile = File;
        var metaURI = metaBase+File; 
        var innerRef = metaURI;
    }
    
    console.log('resource='+path);
    console.log('RDFresource='+innerRef);
    console.log('metafile='+metaFile);
    console.log('metaBase='+metaBase);
    console.log('metaURI='+metaURI);

    // For quick access to those namespaces:
    var RDF = $rdf.Namespace("http://www.w3.org/1999/02/22-rdf-syntax-ns#");
    var WAC = $rdf.Namespace("http://www.w3.org/ns/auth/acl#");
    
    var graph = $rdf.graph();

    var resource = $rdf.sym(innerRef);
    var fetch = $rdf.fetcher(graph);
    
//    console.log("Size: "+graph.statements+"\n")

    fetch.nowOrWhenFetched(metaURI,undefined,function(){
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
        }
        var users = graph.each(resource, WAC('agent'));
        // remove the < > signs from URIs
        $('wac-users').value=users.toString().replace(/\<(.*?)\>/g, "$1");
    });

    // set path value in the title
    $('wac-path').innerHTML=path;
    $('wac-reqpath').innerHTML=requestPath;
}
wac.edit = function(request_path, path) {
    wac.get(request_path, path);
     
    // display the editor
    $('wac-editor').show();
}
wac.hide = function() {
    $('wac-editor').hide();
}

wac.put = function(uri, data) {    
    new HTTP(uri, {
        method: 'put',
        body: data,
        requestHeaders: {'Content-Type': 'text/turtle'}, 
        onSuccess: function() {
            window.location.reload(true);
        }
    });
}

wac.post = function(uri, data) {    
    new HTTP(uri, {
        method: 'post',
        body: data,
        contentType: 'text/turtle',
        onSuccess: function() {
            window.location.reload(true);
        }
    });
}

wac.save = function(elt) {
    var path = $('wac-path').innerHTML;
    var reqPath = $('wac-reqpath').innerHTML;
    var users = $('wac-users').value.split(",");
    var read = $('wac-read').checked;
    var write = $('wac-write').checked;
    var recursive = $('wac-recursive').checked;
    var exists = $('wac-exists').value;
    var owner = $('wac-owner').value;
    
    var metaBase = window.location.protocol+'//'+window.location.host+dirname(reqPath)+'/';

    // Remove preceeding / from path
    if (reqPath.substr(0, 1) == '/')
        reqPath = reqPath.substring(1);

    // remove trailing slash from meta file        
    if (path.substring(path.length - 1) == '/')
        path = path.substring(0, path.length - 1);
        
    // Build the full .meta path URI
    if (path.substr(0, 5) != '.meta') {
        var metaURI = metaBase+'.meta.'+path;
        var innerRef = '#'+reqPath;
    } else {
        var metaURI = metaBase+path;
        path = reqPath;
        var innerRef = metaURI;
    }
    
    console.log('path='+path);
    console.log('reqPath='+reqPath);
    console.log('resource='+metaBase+path);
    console.log('metaBase='+metaBase);
    console.log('metaURI='+metaURI);

    // Create a new graph
    var graph = new $rdf.graph();
    
//    console.log("Size: "+graph.statements+"\n")

    // path
    graph.add(graph.sym(innerRef),
                graph.sym('http://www.w3.org/ns/auth/acl#accessTo'),
                graph.sym(metaBase+reqPath));
                
    // add allowed users
    if ((users.length > 0) && (users[0].length > 0)) {
        var i, n = users.length, user;
        for (i=0;i<n;i++) {
            var user = users[i].replace(/\s+|\n|\r/g,'');
            graph.add(graph.sym(innerRef),
                graph.sym('http://www.w3.org/ns/auth/acl#agent'),
                graph.sym(user));
        }
    } else {
        graph.add(graph.sym(innerRef),
                graph.sym('http://www.w3.org/ns/auth/acl#agentClass'),
                graph.sym('http://xmlns.com/foaf/0.1/Agent'));
    }
    
    // add access modes
    if (read == true) {
        graph.add(graph.sym(innerRef),
            graph.sym('http://www.w3.org/ns/auth/acl#mode'),
            graph.sym('http://www.w3.org/ns/auth/acl#Read'));
    }
    if (write == true) {
        graph.add(graph.sym(innerRef),
            graph.sym('http://www.w3.org/ns/auth/acl#mode'),
            graph.sym('http://www.w3.org/ns/auth/acl#Write'));
    }
    
    if (recursive == true) {
        graph.add(graph.sym(innerRef),
                graph.sym('http://www.w3.org/ns/auth/acl#defaultForNew'),
                graph.sym(reqPath));
    }
    console.log(exists);
    
    // create default rules for the .meta file itself if we create it for the
    // first time
    if (exists == '0') {
        // Add the #Default rule for this domain
        graph.add(graph.sym(metaURI),
                graph.sym('http://www.w3.org/ns/auth/acl#accessTo'),
                graph.sym(metaURI));               
        graph.add(graph.sym(metaURI),
                graph.sym('http://www.w3.org/ns/auth/acl#accessTo'),
                graph.sym(metaURI));
        graph.add(graph.sym(metaURI),
                graph.sym('http://www.w3.org/ns/auth/acl#agent'),
                graph.sym(owner));
        graph.add(graph.sym(metaURI),
                graph.sym('http://www.w3.org/ns/auth/acl#mode'),
                graph.sym('http://www.w3.org/ns/auth/acl#Read'));
        graph.add(graph.sym(metaURI),
                graph.sym('http://www.w3.org/ns/auth/acl#mode'),
                graph.sym('http://www.w3.org/ns/auth/acl#Write'));

        // serialize
        var data = new $rdf.Serializer(graph).toN3(graph);
        // POST the new rules to the server .meta file
        wac.post(metaURI, data);


    } else {
        // copy rules from old meta
        var g = $rdf.graph();
        var fetch = $rdf.fetcher(g);
        var RDF = $rdf.Namespace("http://www.w3.org/1999/02/22-rdf-syntax-ns#");
        var WAC = $rdf.Namespace("http://www.w3.org/ns/auth/acl#");
              
        fetch.nowOrWhenFetched(metaURI,undefined,function(){
            // add accessTo
            graph.add(graph.sym(metaURI),
                graph.sym('http://www.w3.org/ns/auth/acl#accessTo'),
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
            // POST the new rules to the server .meta file
            wac.put(metaURI, data);

        });
    
    }
    
    
    // hide the editor
    $('wac-editor').hide();

    /*
    $('wac-editor').ajaxComplete(function() {
        window.location.reload();
    });*/
}

cloud = {};
cloud.append = function(path, data) {
    data = data || ''
    new HTTP(this.request_url+path, { method: 'post', body: data, contentType: 'text/turtle', onSuccess: function() {
        window.location.reload();
    }});
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
    }});
}
cloud.mkdir = function(path) {
    new HTTP(this.request_url+path, { method: 'mkcol', onSuccess: function() {
        window.location.reload();
    }});
}
cloud.put = function(path, data, type) {
    if (!type) type = 'text/turtle';
    new HTTP(this.request_url+path, { method: 'put', body: data, requestHeaders: {'Content-Type': type}, onSuccess: function() {
        //window.location.reload();
    }});
}
cloud.rm = function(path) {
    new HTTP(this.request_url+path, { method: 'delete', onSuccess: function() {
        window.location.reload();
    }});
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
cloud.alert = function(message, cls) {
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
        console.log(msg);
        cloud.alert(method+' '+msg, cls);
        window.setTimeout("cloud.alert()", 3000);
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

rpc = (method, ev) ->
    $.ajax "api/"+method,
        contentType: "application/json"
        data: JSON.stringify(ev.data)
        type: "POST"
        dataType: "json"
        success: (data, textStatus, xhr) ->
            ev.source.postMessage data, ev.origin

window.addEventListener "message", (ev) ->
    rpc.call(this, ev.data.method, ev)

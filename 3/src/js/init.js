requirejs.config({
    //To get timely, correct error triggers in IE, force a define/shim exports check.
    enforceDefine: false,
    appDir: app.appUrl,
    baseUrl: app.appUrl + 'dist/js',
    paths: {
        jquery: app.appUrl + 'node_modules/jquery/dist/jquery',
        'jquery.event.move': app.appUrl + 'node_modules/jquery.event.move/js/jquery.event.move',
        html5shiv: app.appUrl + 'node_modules/html5shiv/dist/html5shiv',
    },
    map: { },
});

// modify Array object. Add new splice function
Array.prototype.newSplice = function( start, toRemove, insert ) {
    let temp = this.slice(0,start).concat( insert, this.slice( start + toRemove ) );
    this.length = 0;
    this.push.apply( this, temp );
    return this;
};

require(['jquery', 'jstree'], ($, tree) => {

    let $treeCore = $('#tree').jstree({
        'id' : 'jstree'
    });

    $.get(app.ajaxUrl + '?' + $.param({
        action : 'getChildren',
        id : '#'
    }))
    .done((data) => {
        if(data.success) {
            $treeCore.loadNodes(data.data);
        } else {
            console.log(data);
        }
    })
    .fail((data) => {
        console.log(data);
    });

    $treeCore.$tree
        .on('renamed.jstree', function (e, data) {
            $.get(app.ajaxUrl + '?' + $.param({
                action: 'renameNode',
                id: data.id,
                text: data.newName
            })).fail(function (data) {
                console.dir(data);
            });
        })
        .on('createdNode.jstree', function (e, data) {
            $.get(app.ajaxUrl + '?' + $.param({
                action: 'createNode',
                id: data.parentNode.data('id'),
                position : data.position,
                text: data.newNode.text()
            })).done(function (doneData) {

                if(!doneData.success) {
                    console.dir(data);
                    return false;
                }

                $treeCore.updateNode(data.newNode.attr('id'), { dataId : doneData.data.id });

            }).fail(function (data) {
                console.dir(data);
            });
        })
        .on('removedNode.jstree', function (e, data) {
            $.get(app.ajaxUrl + '?' + $.param({
                    action: 'deleteNode',
                    id: data.$liElem.data('id')
                })).done(function (doneData) {

                if(!doneData.success) {
                    console.dir(data);
                    return false;
                }

            }).fail(function (data) {
                console.dir(data);
            });
        })
        .on('moved.jstree', function (e, data) {
            $.get(app.ajaxUrl + '?' + $.param({
                action : 'moveNode',
                id : data.$whatElem.data('id'),
                parentId : data.$whereNode.data('id'),
                position : data.orderPosition
            })).done(function (doneData) {

                if(!doneData.success) {
                    console.dir(data);
                    return false;
                }

            }).fail(function (data) {
                console.dir(data);
            });
    });
});
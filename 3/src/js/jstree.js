(function (factory) {
    "use strict";
    if (typeof define === 'function' && define.amd) {
        define(['jquery', 'jquery.event.move'], factory);
    }
    else if(typeof module !== 'undefined' && module.exports) {
        module.exports = factory(require('jquery', 'jquery.event.move'));
    }
    else {
        factory(jQuery);
    }
}(function ($, undefined) {
    "use strict";

    // a little jquery plugin for reverse selection
    if(!$.fn.reverse) $.fn.reverse = [].reverse;

    // set callback function on any event
    if(!$.fn.onAnyEvent) $.fn.onAnyEvent = function(cb){
        for(let k in this[0])
            if(k.search('on') === 0)
                this.on(k.slice(2), function(e){
                    cb.apply(this,[e]);
                });
        return this;
    };

    // maybe included original library? jstree rocks!
    if($.jstree) {
        return;
    }

    $.jstree = {
        core : {}
    };

    $.fn.jstree = function (args) {
        let core = new $.jstree.core(args.id);
        core.init(this, args);
        return core;
    };

    $.jstree.core = function (id) {
        this._id = id;
        this.$tree = null;
        this.itemsCnt = 0;
        this.$clickedItem = null;
        this.$movePointer = null;
        this.$moveTitle = null;
        this.$contextmenu = null;
        this.cm = {
            $target : null
        };
        this.movePointer = {
            $lastTarget : null,
            cursorPosition : '', // 'top', 'center', 'bottom',
            $onElemNow : null
        };
        this.options = {
            id                  : "jstree",
            iconNodeClosed      : "hand-rock-o",
            iconNodeOpened      : "hand-paper-o",
            iconMovePointer     : "fighter-jet",
            iconMoveTitleOk     : "check",
            iconMoveTitleCancel : "ban",
            lineHeight          : 24
        };
        this.state = {
            cmOpened : false
        }
    };

    $.jstree.core.prototype = {

        init : function (el, options) {
            this.options = $.extend(true, this.options, options);
            this.$tree = this._createNodesList({}, {
                id : this.options.id,
                classes : "jstree jstree-" + this.options.id
            });
            this.$contextmenu = this._contextmenu({
                0 : { class : 'rename', text : "Rename" },
                1 : { class : 'create', text : "Create" },
                2 : { class : 'remove', text : "Remove" },
            });
            this.$movePointer = this._createMovePointer();
            this.$moveTitle = this._createMoveTitle();


            this._bind();
            if(this.options.hasOwnProperty('nodes')) {
                this.loadNodes(this.options['nodes']);
                delete this.options['nodes'];
            }

            $(el).addClass('jstree-wrap');
            $(el).append([this.$tree, this.$contextmenu, this.$movePointer, this.$moveTitle]);

            this.options.lineHeight = $(el).find('a:first').outerHeight();

            this.$tree.trigger('ready');
        },

        /**
         * Load/add nodes to another node
         * @param nodes
         * @param elemId
         */
        loadNodes : function(nodes, elemId = '#' + this._id) {
            this._trigger("nodesLoading");
            let items = this._createNodesList(nodes, { isWrap: false });
            if(items) {
                $(elemId).append(items);
            }
            this._trigger("nodesLoaded");
        },

        // TODO: make full options support
        /**
         * Update node by id
         * @param elemId
         * @param options
         */
        updateNode : function (elemId, options) {
            let $node = $('#' + elemId);

            if(options.hasOwnProperty('dataId')) {
                $node.data('id', options['dataId']);
            }
            if(options.hasOwnProperty('text')) {
                this._renameNode($node.data('id'), $node.find('.jstree-label:first'), options['text']);
            }
        },

        /**
         * Select node
         * @param $liElem
         */
        selectNode : function ($liElem) {
            if( this.$clickedItem !== null ) this.$clickedItem.removeClass('selected');
            this.$clickedItem = $liElem;
            this.$clickedItem.addClass('selected');

            this._trigger('selected', { $liElem : $liElem });
        },

        /**
         * Creates new <li> element
         * @param id
         * @param text
         * @param hasChildren
         * @returns {*|HTMLElement}
         * @private
         */
        _createNode : function (id, text, hasChildren = false) {
            return $(
                "<li id='" + this._id + "-" + ++this.itemsCnt + "' class='jstree-node " + (hasChildren ? "jstree-parent opened" : "jstree-leaf") + "' data-id='" + id + "'>" +
                "<i class='jstree-icon fa " + (hasChildren ? "fa-" + this.options.iconNodeOpened + " opened" : '') + "'></i>" +
                "<a class='jstree-label' draggable='false' href='#'>" + text + "</a>" +
                "</li>");
        },
        /**
         * Creates new <ul> element with list of <li>
         * with nested <ul> elements
         * @param list
         * @param ul
         * @returns {*|HTMLElement}
         * @private
         */
        _createNodesList : function (list, ul = {}) {
            ul = $.extend(true, {
                    isWrap: true,
                    classes: '',
                    id: ''
                }, ul);

            let itemsHTML = "";

            for(let ind in list) {
                if( list.hasOwnProperty( ind ) ) {
                    let hasChildren = list[ind].hasOwnProperty( 'children' ) && Array.isArray(list[ind].children);
                    let $node = this._createNode(
                        list[ind].id,
                        list[ind].text,
                        hasChildren );

                    if(hasChildren) {
                        $node.append( this._createNodesList(list[ind].children) );
                    }

                    itemsHTML += $node[0].outerHTML;
                }
            }

            // wrap by ul
            if(ul.isWrap === true) {
                itemsHTML = "<ul " + (ul.id.length > 0 ? "id='" + ul.id + "' " : "") +
                    "class='jstree-children " + ul.classes + "'>" + itemsHTML + "</ul>";
            }

            return $(itemsHTML);
        },
        /**
         * Creates and adds new node to the $liElem and starts the renaming
         * @param $liElem
         * @private
         */
        _addNode : function ($liElem) {
            let $additive = '',
                position = 0,
                $childrenList = $liElem.children('.jstree-children');

            this._trigger('creatingNode', { parentNode : $liElem });

            if($childrenList.length) {
                $additive = this._createNode('~', 'New node', false);
                position = $childrenList.children('li').length;
                $childrenList.append($additive);
            } else {
                $additive = $(this._createNodesList({
                    0 : {
                        id : '~',
                        text : 'New node',
                        children : false
                    }
                }));
                $liElem
                    .removeClass('jstree-leaf')
                    .addClass('jstree-parent')
                    .append($additive);
            }
            this._rollNode($liElem, 1, false);
            this._renameStart($additive.find('.jstree-label'));

            let $newLiNode = $additive.is('li') ? $additive : $additive.children('li');
            this._trigger('createdNode', {
                parentNode: $liElem,
                newNode : $newLiNode,
                position : position
            });
        },

        /**
         * Change <li> element's parenthood by changing class
         * @param $liElem
         * @param isParent
         * @private
         */
        _changeListParenthood : function ($liElem, isParent = true) {

            if(isParent === true) {
                $liElem.addClass('jstree-parent').removeClass('jstree-leaf');
                $liElem.children('.jstree-icon').addClass('fa-' + this.options.iconNodeOpened);
            } else {
                $liElem.removeClass('jstree-parent').addClass('jstree-leaf');
                $liElem.children('.jstree-icon')
                    .removeClass('fa-' + this.options.iconNodeOpened + ' fa-' + this.options.iconNodeClosed);
            }

        },

        /**
         * Remove node and trigger events
         * @param $liElem
         * @private
         */
        _removeNode : function ($liElem) {
            let $ulElem = $liElem.parent('ul');

            this._trigger('removingNode', { $liElem : $liElem });

            if($ulElem.children('li').length === 1) {
                $ulElem.remove();
            } else {
                $liElem.remove();
            }

            this._trigger('removedNode', { $liElem : $liElem });
        },

        /**
         * Expand or collapse node
         * rollAction values
         * @param $liElem
         * @param rollAction:
         *      1   - expand
         *      0   - toggle
         *      -1  - collapse
         * @param withAnimation
         * @private
         */
        _rollNode : function ($liElem, rollAction = 0, withAnimation = true) {
            let $icon = $liElem.children('.fa');
            if(!$liElem.hasClass('jstree-parent')) return false;

            let $ulChildren = $liElem.children('ul'),
                $iconAndChildren = $icon.add( $ulChildren );

            // TODO: make this logic more clearly
            if(rollAction === 1) { // expand

                $iconAndChildren.removeClass("closed").addClass("opened");
                withAnimation ? $ulChildren.slideDown() : $ulChildren.show();
                $icon
                    .removeClass("fa-" + this.options.iconNodeClosed)
                    .addClass("fa-" + this.options.iconNodeOpened);

            } else if(rollAction === -1) { // collapse

                $iconAndChildren.addClass("closed").removeClass("closed");
                withAnimation ? $ulChildren.slideUp() : $ulChildren.hide();
                $icon
                    .addClass("fa-" + this.options.iconNodeClosed)
                    .removeClass("fa-" + this.options.iconNodeOpened);

            } else if(rollAction === 0) { // toggle

                $iconAndChildren.toggleClass("opened closed");
                withAnimation ? $ulChildren.slideToggle() : $ulChildren.toggle();
                $icon.toggleClass("fa-" + this.options.iconNodeOpened + " fa-" + this.options.iconNodeClosed);

            }
        },

        /**
         * Rename node and trigger event
         * @param id
         * @param labelEl
         * @param newName
         * @private
         */
        _renameNode : function (id, labelEl, newName) {
            $(labelEl).text(newName);
            this._trigger('renamed', { id: id, elem: labelEl, newName: newName });
        },
        /**
         * Hide label, create input for typing new node name and focus on it
         * @param labelEl
         * @private
         */
        _renameStart : function (labelEl) {
            let $labelEl = $(labelEl);
            $labelEl.hide();

            let $input = $("<input type='text' value='" + $labelEl.text() + "' class='jstree-rename'>");
            $labelEl.after($input);
            $input.focus();
        },

        /**
         * Construct context menu html
         * @param actions
         * @returns {*|HTMLElement}
         * @private
         */
        _contextmenu : function (actions) {
            let output = "";

            for(let ind in actions) {
                output += "<li class='jstree-cm-action jstree-cm-" + actions[ind].class + "'>" + actions[ind].text + "</li>";
            }

            return $("<ul class='jstree-cm'>" + output + "</ul>");
        },
        /**
         * Show or hide context menu
         * @param isShow
         * @param left
         * @param top
         * @private
         */
        _contextmenuDisplay : function(isShow = true, left = 0, top = 0) {
            if(isShow) {
                this.$contextmenu.addClass('opened').css({left : left, top : top});
                this.state.cmOpened = true;
            } else {
                this.$contextmenu.removeClass('opened');
                this.state.cmOpened = false;
            }
        },

        /**
         * Creates Move Pointer html
         * @returns {*|HTMLElement}
         * @private
         */
        _createMovePointer : function () {
            return $("<div id='jstree-mvptr' class='jstree-move-pointer'>" +
                "<i class='fa fa-" + this.options.iconMovePointer + "'></i>" +
                "</div>");
        },
        /**
         * Display Move Pointer on some position
         * @param isShow
         * @param left
         * @param top
         * @private
         */
        _movePointerDisplay : function (isShow = true, left = 0, top = 0) {
            if(isShow) {
                top -= this.$movePointer.outerHeight() / 2;
                this.$movePointer.addClass('displayed').css({left : left, top : top});
            } else {
                this.$movePointer.removeClass('displayed');
            }
        },

        /**
         * Create Move Title html
         * @returns {*|HTMLElement}
         * @private
         */
        _createMoveTitle : function () {
            return $("<div id='jstree-mvttl' class='jstree-move-title success'>" +
                "<i class='fa fa-" + this.options.iconMoveTitleOk + "'></i>" +
                "<span class='jstree-mvttl-text'></span>" +
                "</div>");
        },
        /**
         * Display Move Title on some position
         * @param isShow
         * @param left
         * @param top
         * @private
         */
        _moveTitleDisplay : function (isShow = true, left = 0, top = 0) {
            if(isShow) {
                this.$moveTitle.addClass('displayed').css({left : left, top : top});
            } else {
                this.$moveTitle.removeClass('displayed');
            }
        },
        /**
         * Update status that is class and text to Move Title
         * @param status
         * @param text NOTE! If text is empty, he'll be not updated
         * @private
         */
        _moveTitleUpdate : function (status = 'success', text = '') {
            if(status === 'success') {
                this.$moveTitle.addClass('success').removeClass('fail');
                this.$moveTitle.find('.fa').addClass('fa-' + this.options.iconMoveTitleOk)
                    .removeClass('fa-' + this.options.iconMoveTitleCancel);
            } else if(status === 'fail') {
                this.$moveTitle.removeClass('success').addClass('fail');
                this.$moveTitle.find('.fa').addClass('fa-' + this.options.iconMoveTitleCancel)
                    .removeClass('fa-' + this.options.iconMoveTitleOk);
            }

            if(text !== '') {
                this.$moveTitle.find('.jstree-mvttl-text').text(text);
            }
        },

        /**
         * Move event handler
         * @param e
         * @private
         */
        _moveEventHandler : function(e) {
            let $moveEl = $(e.target),
                // impossible get the drag-on-element from "e", so calculating...
                $onElem = $(document.elementFromPoint(e.pageX, this._getScreenPosY(e.pageY))),
                moveTitleStatus = 'fail',
                isOnElemParentForMoveEl = $onElem.parent('li').is($moveEl.parents('li:eq(1)')),
                cursorPos = '';
            this.movePointer.$onElemNow = $onElem;

            // is $onElem label and not $moveEl
            if($onElem.is('.jstree-label') && !$onElem.is($moveEl)
              // $onElem is not child of $moveEl
              && $onElem.parents("#" + $moveEl.parent('li').attr('id')).length <= 0
            ) {

                this.movePointer.$lastTarget = $onElem;
                moveTitleStatus = 'success';

                let pos = $onElem.position(),
                    heightOnEl = $onElem.outerHeight(),
                    left = pos.left - this.$movePointer.outerWidth(),
                    top = 0;
                // if cursor higher than 20% of center drag-on-element
                if(e.pageY >= pos.top && e.pageY <= pos.top + heightOnEl * .4)
                {
                    left = left + 3;
                    top = pos.top;
                    cursorPos = 'top';
                }
                // if cursor lower than 20% of center drag-on-element
                else if(e.pageY >= pos.top + heightOnEl * .6 && e.pageY <= pos.top + heightOnEl)
                {
                    left = left + 3;
                    top = pos.top + heightOnEl;
                    cursorPos = 'bottom';
                }
                // if cursor in center
                else
                {
                    top = pos.top + heightOnEl / 2;
                    cursorPos = 'center';
                }


                if(isOnElemParentForMoveEl === true && cursorPos === 'center') {
                    this._movePointerDisplay(false);
                    this.movePointer.$lastTarget = null;
                }
                else
                {
                    this.movePointer.cursorPosition = cursorPos;
                    this._movePointerDisplay(true, left, top);
                }
            } else {
                this._movePointerDisplay(false);
            }

            if(this.movePointer.$lastTarget === null
                || isOnElemParentForMoveEl === true && cursorPos === 'center')
            {
                this._moveTitleUpdate('fail');
            }
            else if (this.movePointer.$lastTarget !== null)
            {
                this._moveTitleUpdate(moveTitleStatus);
            }
            this._moveTitleDisplay(true, e.pageX, e.pageY);
        },

        _moveNode : function($whatElem, $whereNode, whereDirection = 'center') {
            let whereHasChildren = $whereNode.children('ul').length > 0,
                // *has another child than $whatLiElem
                whatParHasChild = $whatElem.parent('ul').children('li').length > 1,
                liFinalOrderPos = 0;

            this._trigger('moving', { $whatElem : $whatElem, $whereNode : $whereNode });

            // set parent <li> leaf state and remove <ul>
            let ifWhatParHasLastChild = () => {
                if(whatParHasChild === false) {
                    let $ul = $whatElem.parent('ul');
                    this._changeListParenthood($ul.closest('li'), false);
                    $ul.remove();
                }
            };

            if(whereDirection === 'top')
            {
                ifWhatParHasLastChild();
                liFinalOrderPos = $whereNode.parent('ul').children('li').index( $whereNode );
                $whereNode.before( $whatElem );
            }
            else if(whereDirection === 'bottom')
            {
                ifWhatParHasLastChild();
                liFinalOrderPos = $whereNode.parent('ul').children('li').index( $whereNode ) +  1;
                $whereNode.after( $whatElem );
            }
            else // if "center" actually
            {
                if(whereHasChildren === false && whatParHasChild === false)
                {
                    // move full What <ul> and set parent of <ul> that it is leaf
                    let $ul = $whatElem.parent('ul');
                    this._changeListParenthood($ul.closest('li'), false);
                    this._changeListParenthood($whereNode, true);
                    $whatElem = $ul;
                }
                else if(whereHasChildren === false && whatParHasChild === true)
                {
                    // create Where <ul>, set him that it is parent
                    // and init $whereNode by Where <ul>
                    let $ul = this._createNodesList({});
                    this._changeListParenthood($whereNode, true);
                    $whereNode.append($ul);
                    $whereNode = $ul;
                }
                else if(whereHasChildren === true && whatParHasChild === false)
                {
                    // remove full What <ul> and set parent of <ul> that it is leaf
                    ifWhatParHasLastChild();
                    $whereNode = $whereNode.children('ul');

                    // set the last position among children
                    liFinalOrderPos = $whereNode.children('li').length;
                }
                else
                {
                    $whereNode = $whereNode.children('ul');

                    // set the last position among children
                    liFinalOrderPos = $whereNode.children('li').length;
                }

                $whatElem.appendTo( $whereNode );
            }

            // is $whereNode <li>?
            $whereNode = $whereNode.is('li') ? $whereNode : $whereNode.closest('li');
            // now is. So get parent <li> of $whatElem
            $whereNode = $whatElem.parents('li:first').is($whereNode) ? $whereNode : $whereNode.parents('li:first');
            $whatElem = $whatElem.is('li') ? $whatElem : $whatElem.children('li');

            this._trigger('moved', { $whatElem : $whatElem, $whereNode : $whereNode, orderPosition : liFinalOrderPos });
        },

        /**
         * Trigger jstree events
         * @param ev
         * @param data
         * @param handlerElem
         * @private
         */
        _trigger : function (ev, data = {}, handlerElem = this.$tree) {
            data.instance = this;
            $(handlerElem).triggerHandler(ev + ".jstree", data);
        },

        _getScreenPosY(pageY) {
            return pageY - window.scrollY;
        },

        _unbind : function () {
            this.$tree.off('.jstree');
            $(document).off('.jstree-' + this._id);
        },

        _bind : function () {
            this.$tree
                // TODO: intercept events only if needed
                .on("click dblclick touchstart touchend focus blur", "*", (e) => {
                    this._trigger(e.type, { event: e });
                })
                .on("touchstart.jstree click.jstree", ".jstree-icon", (e) => {
                    this._contextmenuDisplay(false); // TODO: call to close context menu function once

                    let $parent = $(e.currentTarget).parent('li');
                    this._rollNode($parent, 0);

                    return false;
                })
                .on("touchstart.jstree click.jstree", ".jstree-label", (e) => {
                    this._contextmenuDisplay(false);

                    this.selectNode( $(e.currentTarget).parent('li') );

                    return false;
                })
                .on("dblclick.jstree", ".jstree-label", (e) => {
                    this._contextmenuDisplay(false);

                    this._renameStart( e.currentTarget );

                    return false;
                })
                .on("blur.jstree", ".jstree-rename", (e) => {
                    let $target = $(e.currentTarget);
                    let $label = $target.prev('.jstree-label');
                    if($target.val() !== $label.text()) {
                        this._renameNode($target.parent('li').data('id'), $label, $target.val());
                    }

                    $target.remove();
                    $label.show();
                })
                .on("nodesLoaded.jstree", (e) => {
                    //this._unbind();
                    //this._bind();
                })

                // Context menu
                .on("contextmenu.jsree", ".jstree-label", (e) => {
                    let $target = this.cm.$target = $(e.currentTarget);

                    this.selectNode($target.parent('li'));

                    let left = $target.position().left;
                    let top = $target.position().top + $target.outerHeight();

                    this._contextmenuDisplay(true, left, top);
                    return false;
                });

            this.$contextmenu
                .on("touchstart.jstree click.jsree", ".jstree-cm-rename", (e) => {
                    if(this.state.cmOpened === false) return false;

                    this._renameStart( this.cm.$target );
                })
                .on("touchstart.jstree click.jsree", ".jstree-cm-create", (e) => {
                    if(this.state.cmOpened === false) return false;

                    this._addNode( this.cm.$target.parent('li') );
                })
                .on("touchstart.jstree click.jsree", ".jstree-cm-remove", (e) => {
                    if(this.state.cmOpened === false) return false;

                    this._removeNode( this.cm.$target.parent('li') );
                });


            // TODO: intercept and set move.jstree event
            this.$tree
                .on("movestart", ".jstree-label", (e) => {
                    this._moveTitleUpdate('fail', $(e.currentTarget).text());

                    // clear variables
                    this.movePointer.$lastTarget = null;
                    this.movePointer.cursorPosition = '';
                })
                .on("move", $.proxy(this._moveEventHandler, this))
                .on("move", ".jstree-label", $.proxy(this._moveEventHandler, this))
                .on("moveend", (e) => {
                    this._movePointerDisplay(false);
                    this._moveTitleDisplay(false);

                    if(this.movePointer.$onElemNow.is('.jstree-label') && this.movePointer.$lastTarget !== null) {
                        this._moveNode(
                            $(e.target).parent('li'),
                            this.movePointer.$lastTarget.parent('li'),
                            this.movePointer.cursorPosition
                        );
                    }

                    // clear variables
                    this.movePointer.$lastTarget = null;
                    this.movePointer.cursorPosition = '';
                });

            $(document)
                .on('touchstart click', (e) => {
                    this._trigger('click', {}, document);
                })
                .on('touchstart.jstree click.jstree', (e) => {
                    this._contextmenuDisplay(false);
                });
        },
    }
}));

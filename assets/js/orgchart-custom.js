'use strict';

(function ($) {

    $(function () {

        $(document).on('click', '.expandAll', function () {
            var $temp = oc.$chart
                .find('.hidden, .isCollapsedDescendant, .isChildrenCollapsed')
                .removeClass('hidden isCollapsedDescendant isChildrenCollapsed');
            $temp[0].offsetWidth;
            $temp.find('.slide-up').removeClass('slide-up');
            $(this).removeClass("expandAll");
            $(this).addClass("collapseAll");
            $(this).html("Collapse All");
        });

        $(document).on('click', '.collapseAll', function () {
            oc.hideChildren(oc.$chart.find('.node:first'));
            $(this).addClass("expandAll");
            $(this).removeClass("collapseAll");
            $(this).html("Expand All");
        });
        

        var dataset = [
            { id: 1, name: 'ELECTRONICS', desc: 'Lorem Ipsum is simply', parent_id: null },
            { id: 2, name: 'TELEVISIONS', desc: 'dummy text of the printing', parent_id: 1 },
            { id: 3, name: 'TUBE', desc:'and typesetting industry. ... be distracted', parent_id: 2 },
            { id: 4, name: 'LCD', desc:' by the readable content of a', parent_id: 2 },
            { id: 5, name: 'PLASMA', desc: 'page when looking at its layout.', parent_id: 2 },
            { id: 6, name: 'PORTABLE ELECTRONICS', desc: 'Lorem Ipsum is simply', parent_id: 1 },
            { id: 7, name: 'MP3 PLAYERS', desc: 'dummy text of the printing', parent_id: 6 },
            { id: 8, name: 'FLASH', desc:'and typesetting industry. ... be distracted', parent_id: 7 },
            { id: 9, name: 'CD PLAYERS', desc: 'page when looking at its layout.', parent_id: 6 },
            { id: 10, name: '2 WAY RADIOS', desc: 'Lorem Ipsum is simply', parent_id: 6 }];

        var getId = function () {
            return (new Date().getTime()) * 1000 + Math.floor(Math.random() * 1001);
        };
        var datasource = {};

        dataset.forEach(function (item, index) {
            if (!item.parent_id) {
                delete item.parent_id;
                Object.assign(datasource, item);
            } else {
                var jsonloop = new JSONLoop(datasource, 'id', 'children');
                jsonloop.findNodeById(datasource, item.parent_id, function (err, node) {
                    if (err) {
                        console.error(err);
                    } else {
                        delete item.parent_id;
                        if (node.children) {
                            node.children.push(item);
                            var b = 2;
                        } else {
                            node.children = [item];
                            var a = 1;
                        }
                    }
                });
            }
        });

        var oc = $('#chart-container').orgchart({
            'data': datasource,
            'parentNodeSymbol': 'fa-sitemap',

            'nodeContent': 'title',
            'draggable': true,
            'zoom': true,
            'visibleLevel': 2,
            'pan': true,
            'nodeId': 'id',
            'createNode': function ($node, data) {
                $node.on('dblclick', function (event) {
                    var $chartContainer = $('#chart-container');
                    var nodeVals = ["test"];

                    //console.log(nodeVals);

                    if (!$node.siblings('.nodes').length) {
                        var rel = nodeVals.length > 1 ? '110' : '100';
                        oc.addChildren($node, nodeVals.map(function (item) {
                            return { 'name': item, 'desc': item, 'relationship': rel, 'id': getId() };
                        }));
                    } else {
                       oc.addSiblings($node.siblings('.nodes').find('.node:first'), nodeVals.map(function (item) {
                            return { 'name': item, 'desc': item, 'relationship': '110', 'id': getId() };
                        }));
                    };

                    //on append show childrens
                    $(this).closest(".isChildrenCollapsed").removeClass('isChildrenCollapsed');
                    $(this).siblings("ul").removeClass('hidden');
                    $(this).siblings("ul").children().find(".slide-up").removeClass('slide-up');
                    var inputFocus = $('.org-input');
                    inputFocus.focus();
                    //node title
                    // $('.node-level-title').remove();
                    // $('.nodes li:not(last-child > .node)').remove(".node-level-title")
                    // $('.nodes li:last-child > .node').append('<div class="node-level-title"><div class="card-body p-3 text-primary text-white">Level Title</div></div>');
                });

                //   right click open form
                $node.on("contextmenu", function(e) {
                    e.preventDefault();
                    $("#org-drawer").addClass("open-org-drawer");
                });
                $("#org-drawer-close").on("click", function(e) {
                    $("#org-drawer").removeClass("open-org-drawer");
                });

                $node.on("contextmenu", function(e) {
                    e.preventDefault();
                    $("#org-drawer").addClass("open-org-drawer");
                });

            }
            //});
        });
        oc.$chartContainer.on("click", ".node", function () {
            var $this = $(this);
            $("#selected-node").val($this.find(".title").text()).data("node", $this);
        });
        $(document).on('click', '#btn-delete-nodes', function () {
           var $node = $("#selected-node").data("node");
            oc.removeNodes($node);
            $("#selected-node").val("").data("node", null);
        });
        $(".orgchart #1 #btn-delete-nodes").remove()


        //center node
        
        });
})(jQuery, JSONLoop);




var keyboardSupport = document.getElementById('noUi-zoom-slider')
noUiSlider.create(keyboardSupport, {
    start: 100,
	// direction: 'rtl',
	// orientation: 'vertical',
    tooltips: false,
    format: {
    from: Number,
    to: function(value) {
        return (parseInt(value));
    }
    },
    keyboardSupport: true,      // Default true
    keyboardDefaultStep: 5,     // Default 10
    keyboardPageMultiplier: 10, // Default 5
    range: {
        'min': 60,
        'max': 150
    }
});

function manualStep (direction){
		var currentPosition = parseInt(keyboardSupport.noUiSlider.get());
    var stepSize = 10;
    if(direction == 'f'){
    	currentPosition += stepSize;
    }
    if(direction == 'b'){
    	currentPosition -= stepSize;
    }
  	currentPosition = (Math.round(currentPosition / stepSize) * stepSize);
    //alert(currentPosition)
     keyboardSupport.noUiSlider.set(currentPosition); 
}
document.getElementById('stepforward').onclick = function() {manualStep("f")};
document.getElementById('stepback').onclick = function() {manualStep("b")};

keyboardSupport.noUiSlider.on('update', function (values, handle) {
    $(".orgchart").css({ "transform": "scale("+values[handle]/100+")" });
	// $('.orgchart').css({ '-moz-transform': 'scale(' + values[handle]/100 + ')' });
});
$( document ).ready(function() {
	keyboardSupport.noUiSlider.on('update', function (values, handle) {
        $(".orgchart").css({ "transform": "scale("+values[handle]/100+")" });
        // $('.orgchart').css({ '-moz-transform': 'scale(' + values[handle]/100 + ')' });
});
// keyboardSupport.noUiSlider.set("60");
});
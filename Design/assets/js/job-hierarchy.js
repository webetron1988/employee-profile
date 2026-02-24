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
            { id: 1, className:'parentCard', name: 'Google', jobId: '', parent_id: null },
            { id: 2, name: 'IT and ITES', jobId: '#JF1', parent_id: 1 },
            { id: 3, name: 'Accounts', jobId:'#JF3', parent_id: 1 },
            { id: 4, name: 'Agriculture', jobId:'#JF4', parent_id: 1 },
            { id: 5, name: 'Bio Tech', jobId: '#JF5', parent_id: 1 },
            { id: 6, name: 'Data SC', jobId: '#JF2', parent_id: 2 },
            { id: 7, name: 'AI ML', jobId: '#JF6', parent_id: 2 },
            { id: 8, name: 'Mobile Tech', jobId:'#JF9', parent_id: 2 },
            { id: 9, name: 'Web Development', jobId: '#JF7', parent_id: 2 },
            { id: 10, name: 'UI/Ux Development', jobId: '#JF8', parent_id: 9 }];

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

                });

                //   right click open form
                // $node.on("contextmenu", function(e) {
                //     e.preventDefault();
                //     $("#org-drawer").addClass("open-org-drawer");
                // });
                // $("#org-drawer-close").on("click", function(e) {
                //     $("#org-drawer").removeClass("open-org-drawer");
                // });

                // $node.on("contextmenu", function(e) {
                //     e.preventDefault();
                //     $("#org-drawer").addClass("open-org-drawer");
                // });

                
            }
            //});
        });
        oc.$chartContainer.on("click", ".node", function () {
            var $this = $(this);
            var id = $(this).attr("id");
            $("#selected-node").val($this.find(".title").text()).data("node", $this);
            alert("This node id is" + " " + id)
        });
        $(document).on('click', '#btn-delete-nodes', function () {
           var $node = $("#selected-node").data("node");
            oc.removeNodes($node);
            $("#selected-node").val("").data("node", null);
        });
        $(".orgchart #1 #btn-delete-nodes").remove()
    });
})(jQuery, JSONLoop);




var keyboardSupport = document.getElementById('noUi-zoom-slider')
noUiSlider.create(keyboardSupport, {
    start: 90,
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
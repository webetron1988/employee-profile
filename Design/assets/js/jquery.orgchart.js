(function($) {
    $.fn.orgChart = function(options) {
        var opts = $.extend({}, $.fn.orgChart.defaults, options);
        return new OrgChart($(this), opts);        
    }

    $.fn.orgChart.defaults = {
        'nodeContent': 'title',
        'draggable': true,
        data: [{id:1, name:'Root', parent: 0}],
        showControls: true,
        allowEdit: false,
        onAddNode: null,
        onDeleteNode: null,
        onClickNode: null,
        newNodeText: 'Add Child'
    };

    function OrgChart($container, opts){
        var data = opts.data;
        var nodes = {};
        var rootNodes = [];
        this.opts = opts;
        this.$container = $container;
        var self = this;

        this.draw = function(){
            $container.empty().append(rootNodes[0].render(opts));
            $container.find('.node').click(function(){
                if(self.opts.onClickNode !== null){
                    self.opts.onClickNode(nodes[$(this).attr('node-id')]);
                }
            });
             //   double click add child
            $(".node").dblclick(function(){
                var thisId = $(this).attr('node-id');

                if(self.opts.onAddNode !== null){
                    self.opts.onAddNode(nodes[thisId]);
                }
                else{
                    self.newNode(thisId);
                }
                e.stopPropagation();
              }); 

            //   right click open form
              $(".node").on("contextmenu", function(e) {
                e.preventDefault();
                $("#org-drawer").addClass("open-org-drawer");
              });
              $("#org-drawer-close").on("click", function(e) {
                $("#org-drawer").removeClass("open-org-drawer");
              });
            //   $("#orgChartContainer").on("click", function(e) {
            //     $("#org-drawer").removeClass("open-org-drawer");
            //   })
              

            // if(opts.allowEdit){
            //     $container.find('.node .structure-tile').click(function(e){
            //         var thisId = $(this).parent().attr('node-id');
            //         self.startEdit(thisId);
            //         e.stopPropagation();
            //     });
            // }

            // add "add button" listener
            // $container.find('.org-add-button').click(function(e){
            //     var thisId = $(this).parent().attr('node-id');

            //     if(self.opts.onAddNode !== null){
            //         self.opts.onAddNode(nodes[thisId]);
            //     }
            //     else{
            //         self.newNode(thisId);
            //     }
            //     e.stopPropagation();
            // });

            $container.find('.org-del-button').click(function(e){
                var thisId = $(this).parent().attr('node-id');

                if(self.opts.onDeleteNode !== null){
                    self.opts.onDeleteNode(nodes[thisId]);
                }
                else{
                    self.deleteNode(thisId);
                }
                e.stopPropagation();
            });
        }

        this.startEdit = function(id){
            var inputElement = $('<input class="org-input form-control w-100" type="text" value="'+nodes[id].data.name+'"/>');
            $container.find('div[node-id='+id+'] .structure-tile h2').replaceWith(inputElement);
            var commitChange = function(){
                var h2Element = $('<h2>'+nodes[id].data.name+'</h2>');
                
                // if(opts.allowEdit){
                //     h2Element.click(function(){
                //         self.startEdit(id);
                //     })
                // }
                inputElement.replaceWith(h2Element);
            }  
            inputElement.focus();
            inputElement.keyup(function(event){
                if(event.which == 13){
                    commitChange();
                }
                else{
                    nodes[id].data.name = inputElement.val();
                }
            });
            inputElement.blur(function(event){
                commitChange();
            })
        }

        this.newNode = function(parentId){
            var nextId = Object.keys(nodes).length;
            while(nextId in nodes){
                nextId++;
            }

            self.addNode({id: nextId, name: '', parent: parentId});
        }

        this.addNode = function(data){
            var newNode = new Node(data);
            nodes[data.id] = newNode;
            nodes[data.parent].addChild(newNode);

            self.draw();
            self.startEdit(data.id);
        }

        this.deleteNode = function(id){
            for(var i=0;i<nodes[id].children.length;i++){
                self.deleteNode(nodes[id].children[i].data.id);
            }
            nodes[nodes[id].data.parent].removeChild(id);
            delete nodes[id];
            self.draw();
        }

        this.getData = function(){
            var outData = [];
            for(var i in nodes){
                outData.push(nodes[i].data);
            }
            return outData;
        }

        // constructor
        for(var i in data){
            var node = new Node(data[i]);
            nodes[data[i].id] = node;
        }

        // generate parent child tree
        for(var i in nodes){
            if(nodes[i].data.parent == 0){
                rootNodes.push(nodes[i]);
            }
            else{
                nodes[nodes[i].data.parent].addChild(nodes[i]);
            }
        }

        // draw org chart
        $container.addClass('orgChart');
        self.draw();
    }

    function Node(data){
        this.data = data;
        this.children = [];
        var self = this;

        this.addChild = function(childNode){
            this.children.push(childNode);
        }

        this.removeChild = function(id){
            for(var i=0;i<self.children.length;i++){
                if(self.children[i].data.id == id){
                    self.children.splice(i,1);
                    return;
                }
            }
        }

        this.render = function(opts){
            var childLength = self.children.length,
                mainTable;

            mainTable = "<table cellpadding='0' cellspacing='0' border='0' id='main-structure-table'>";
            var nodeColspan = childLength>0?2*childLength:2;
            mainTable += "<tr><td colspan='"+nodeColspan+"'>"+self.formatNode(opts)+"</td></tr>";

            if(childLength > 0){
                var downLineTable = "<table cellpadding='0' cellspacing='0' border='0'><tr class='lines x'><td class='line left half'><div class='viewChild'></div></td><td class='line right half'></td></table>";
                mainTable += "<tr class='lines'><td colspan='"+childLength*2+"'>"+downLineTable+'</td></tr>';

                var linesCols = '';
                for(var i=0;i<childLength;i++){
                    if(childLength==1){
                        linesCols += "<td class='line left half'></td>";    // keep vertical lines aligned if there's only 1 child
                    }
                    else if(i==0){
                        linesCols += "<td class='line left'></td>";     // the first cell doesn't have a line in the top
                    }
                    else{
                        linesCols += "<td class='line left top'></td>";
                    }

                    if(childLength==1){
                        linesCols += "<td class='line right half'></td>";
                    }
                    else if(i==childLength-1){
                        linesCols += "<td class='line right'></td>";
                    }
                    else{
                        linesCols += "<td class='line right top'></td>";
                    }
                }
                mainTable += "<tr class='lines v'>"+linesCols+"</tr>";

                mainTable += "<tr class='thisChilds'>";
                for(var i in self.children){
                    mainTable += "<td colspan='2' class='org-tree-parent'>"+self.children[i].render(opts)+"</td>";
                }
                mainTable += "</tr>";
            }
            mainTable += '</table>';

            return mainTable;
        }

        this.formatNode = function(opts){
            var nameString = '',
                descString = '';
            if(typeof data.name !== 'undefined'){
                nameString = '<div class="structure-tile flex-column"><figure><span><img src="assets/media/images/google-xl.png"></span></figure><div class="d-flex flex-column text-center justify-content-center"><h2>'+self.data.name+'</h2><h6 class="font-weight-normal text-dark-50 mt-1">bengaluru india</h6></div><div class="d-flex flex-wrap justify-content-center mb-3"><span class="text-success d-flex m-2"><img src="assets/media/images/user-success.svg" class="mr-1 h-12 align-self-center"> 36 Members</span><span class="text-warning d-flex m-2"><img class="mr-1 h-12 align-self-center" src="assets/media/images/bullet-calendar-warning.svg"> 500 Serving Notice</span><span class="text-danger d-flex m-2"><img class="mr-1 h-12 align-self-center" src="assets/media/images/portfolio-danger.svg"> 50 Critical positions</span></div>';

                // assets/media/svg/icons/chart/department-icon.svg
                // assets/media/svg/icons/chart/segment-icon.svg
                // assets/media/svg/icons/chart/unit-icon.svg
            }
            if(typeof data.description !== 'undefined'){
                descString = '<p>'+self.data.description+'</p>';
            }
            if(opts.showControls){
                var buttonsHtml = '<div class="dropdown dropdown-inline ml-auto org-chart-actions"> <a href="#" class="p-1 btn btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="ki ki-bold-more-hor"></i> </a> <div class="dropdown-menu dropdown-menu-sm dropdown-menu-right" style=""><ul class="navi navi-hover my-0"><li class="py-2"><a href="" class="text-dark-50"><img src="assets/media/images/edit-icon.svg" class="h-14 mr-2" alt=""> <span class="align-self-center">Edit</span></a></li> <li class="py-2"><a href="" class="text-dark-50"><i class="flaticon-delete mr-2"></i> <span class="align-self-center">Delete</span></a></li></ul></div></div>';
            }
            else{
                buttonsHtml = '';
            }
            return "<div class='node' node-id='"+this.data.id+"' style='border-top-color:#8a84e8'>"+nameString+descString+buttonsHtml+"</div>";
        }
    }
})(jQuery);



// var canvas = document.getElementById("canvas");
//     var ctx = canvas.getContext("2d");
//     var data = "<svg xmlns='http://www.w3.org/2000/svg' width='360' height='360'>" +
//                  "<foreignObject width='100%' height='100%'>" +
//                    "<div xmlns='http://www.w3.org/1999/xhtml'>" +
//                       "<a href=''>abc</a>" +
//                    "</div>" +
//                  "</foreignObject>" +
//                "</svg>";
//     var DOMURL = self.URL || self.webkitURL || self;
//     var img = new Image();
//     var svg = new Blob([data], {type: "image/svg+xml;charset=utf-8"});
//     var url = DOMURL.createObjectURL(svg);
//     img.onload = function() {
//         ctx.drawImage(img, 0, 0);
//         DOMURL.revokeObjectURL(url);
//     };
//     img.src = url;






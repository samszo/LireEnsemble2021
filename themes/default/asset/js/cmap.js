class cmap {
    constructor(params) {
        var me = this;
        this.width = params.width ? params.width : 1400;
        this.height = params.height ? params.height : 800;
        this.tables = params.tables
        this.links = params.links
        this.site_url = params.site_url
        this.user_id = params.user_id
        this.arr_pos = []

        let svg = d3.select('#svg')
            .attr('width',me.width)
            .attr('height',me.height)

        let colsHeight = 20//Hauteur du champ
        , tableTitleHeight = 25//Hauteur du champ
        , tableFootHeight = 25//Hauteur du champ
        , colsWidth = 130
        , offLine = 20//Décalage de nœud
        , colorTitleEntities = d3.scaleSequential()
            .domain([0,9])
            .interpolator(d3.interpolate("white", "red"))
        , colorProprietes = d3.scaleSequential()
            .domain([0,9])
            .interpolator(d3.interpolate("white", "orange"))
        , colorLinks = d3.scaleSequential()
            .domain([0,9])
            .interpolator(d3.interpolate("brown", "blue"));

        let btnText = {
            leftOpen:'',//'Extension de champ',
            leftClose:'',//'Réduire le champ',
            rightOpen:'',//'Élargissement des relations',
            rightClose:'',//'Effondrement de la relation',
        }

        //Définition du style
        let lineStyle = {
            strokeDefault:'#80848f',
            strokeWidthDefault:'1',
            strokeLight:'#19be6b',
            strokeWidthLight:'1',
        }
        let colsStyle = {
            fillDefalt:'#ccc',
            fillLight:'#19be6b',
        }

        let initObj = initData(svg);
        initObj.svgAddNode()
        window.setTimeout(initObj.changePort,100)

        function initData(svgEle){
            //initialisation
            let g, gPosition;

            colorTitleEntities.domain([0,me.tables[0].max_nb_items]);
            colorProprietes.domain([0,me.tables[0].max_nb_pros]);
            colorLinks.domain([0,me.tables[0].max_nb_links]);
            
            initSVG()
            //Première exécution
            function initSVG(){
                let zoom = d3.zoom()
                    .scaleExtent([0.1,10]) // normal [0.1,10]
                    .on("zoom",function(d){
                        g.attr("transform",function(){//initialisation
                            return `translate(${d3.event.transform.x+gPosition.x},${d3.event.transform.y+gPosition.y}) scale(${d3.event.transform.k})`
                        })
                    })
                svgEle.call(zoom).on('dblclick.zoom',null);
                g = svgEle.append('g')
                g.append("g").attr('class','links')
            }

            //Position initiale
            function changePort(){
                gPosition = svgEle.select('g').node().getBBox()
                gPosition.x = -gPosition.x+20
                gPosition.y = -gPosition.y+20

                g.transition().delay(0).attr('transform',function(){
                    return `translate(${gPosition.x},${gPosition.y}) scale(1)` // normal scale(1)
                })
            }

            //dessiner

            let allTables, addTablesData, allLinks, addLinksData, allColsGs, addColsGsData,allFooter, addFooterData;

            function svgAddNode(){
                addTables()
                addLinks()
                initDrag()
            }

            function addTables(){
                addTablesData = g.selectAll('.tbClass').data(me.tables)
                    .enter()
                    .append('g')
                    .attr('class','tbClass')
                    .attr('id',function(d){return d.id})
                    .attr("transform", function(d){
                        return `translate(${d.x},${d.y})`
                    })
                    .attr("cx", function(d) { return d.x; })
                    .attr("cy", function(d) { return d.y; })

                allTables = g.selectAll('.tbClass').data(me.tables)

                addTitle()
                addColsWrap()
                addFooter()
            }
            function delTables(){
                // allTables = g.selectAll('.tbClass').data(tables)
                allTables.exit().remove()
                // delLinks()
            }
            function initDrag(){
                allTables.call(d3.drag()//Ajouter un événement glisser
                    //.on('start',startFn)
                    .on('drag',dragFn)
                    .on('end',endFn)
                )
                //Ajouter la souris sur l'événement
                allTables.on('mouseenter',function(d){
                    hoverLight.call(this,d,1)
                })
                    .on('mouseleave',function(d){
                        hoverLight.call(this,d,0)
                    })
            }

            //Calcul de connexion gauche et droite
            function linkFn(d){
                let res = []

                let source_x = me.tables[d.source].x;
                let source_y = me.tables[d.source].y;

                let target_x = me.tables[d.target].x;
                let target_y = me.tables[d.target].y;

                if(source_x < target_x){
                    res[2] = [target_x - offLine, target_y]
                    res[3] = [target_x, target_y]
                    if((source_x + colsWidth + offLine*0) < target_x ){
                        res[0] = [source_x + colsWidth, source_y]
                        res[1] = [source_x + colsWidth + offLine, source_y]
                    }else{
                        res[0] = [source_x, source_y]
                        res[1] = [source_x - offLine, source_y]
                    }
                }else{
                    res[1] = [source_x - offLine, source_y]
                    res[0] = [source_x, source_y]
                    if((target_x + colsWidth + offLine*0) < source_x ){
                        res[3] = [target_x + colsWidth, target_y]
                        res[2] = [target_x + colsWidth + offLine, target_y]
                    }else{
                        res[3] = [target_x, target_y]
                        res[2] = [target_x  - offLine, target_y]
                    }
                }
                res[0][1] += d.sourceIndex*colsHeight + colsHeight/2
                res[1][1] += d.sourceIndex*colsHeight + colsHeight/2
                res[2][1] += d.targetIndex*colsHeight + colsHeight/2
                res[3][1] += d.targetIndex*colsHeight + colsHeight/2

                return res.map(v=>v.join(',')).join(' ')
            }

            function dragFn(d, i){//Faire glisser
                d.x += d3.event.dx
                d.y += d3.event.dy
                d3.select(this).attr("cx", d.x).attr("cy", d.y);

                allTables.attr("transform", function(d){
                    return `translate(${d.x},${d.y})`
                })

                addLinksData.each(function(l, li) {
                    if (l.source == i) {
                        d3.select(this).attr("x1", d.x).attr("y1", d.y).attr('points',linkFn);;
                    } else if (l.target == i) {
                        d3.select(this).attr("x2", d.x).attr("y2", d.y).attr('points',linkFn);;
                    }
                });
            }

            function endFn(d){//Faites glisser la fin
                // Ajouter: position après glissement
                me.arr_pos[d.id] = [d.id_entite, d.x, d.y]
                me.func_arr_pos(me.arr_pos)
            }

            //Ajouter une connexion
            function addLinks(){
                addLinksData = d3.select('.links')
                    .selectAll("link")
                    .data(me.links)
                    .enter()
                    .append("polyline")
                    .attr("fill",'none')
                    .attr("stroke", d=>colorLinks(d.nb))
                    .attr("stroke-width",1)
                    .attr('class','link-polyline')
                    .attr("x1", function(l) {
                        var sourceNode = me.tables.filter(function(d, i) {
                            return i == l.source
                        })[0];
                        d3.select(this).attr("y1", sourceNode.y);
                        return sourceNode.x
                    })
                    .attr("x2", function(l) {
                        var targetNode = me.tables.filter(function(d, i) {
                            return i == l.target
                        })[0];
                        d3.select(this).attr("y2", targetNode.y);
                        return targetNode.x
                    })
                    .attr("marker-end", "url(#arrow)")
                    .attr('points',linkFn);

                //arrow
                svg.append("svg:defs").append("svg:marker")
                    .attr("id", "arrow")
                    .attr("refX", 12)
                    .attr("refY", 6)
                    .attr("markerWidth", 30)
                    .attr("markerHeight", 30)
                    .attr("markerUnits","userSpaceOnUse")
                    .attr("orient", "auto")
                    .append("path")
                    .attr("d", "M 0 0 12 6 0 12 3 6")
                    .style("fill", "#000");
            }
            function delLinks(){
                // allLinks =  d3.select('.links')
                //   .selectAll('polyline')
                //   .data(links)
                allLinks.exit().remove()
            }
            //Ajouter une bordure
            function gsAddBorder(){
                addTablesData.append('rect')
                    .attr('height',function(d){
                        return colsHeight*d.cols.length + tableTitleHeight
                    })
                    .attr('width',function(d){
                        return colsWidth
                    })
                    .attr('rx',5)
                    .attr('stroke','#5cadff')
                    .attr('stroke-width','1px')
                    .attr('fill-opacity','0')
            }
            //Ajouter un en-tête
            function addTitle() {
                let tTitle = addTablesData.append('g')
                    .attr('class','table-title')

                tTitle.append('rect')
                    // .attr('rx',5)
                    .attr('height',function(d){
                        return tableTitleHeight
                    })
                    .attr('width',function(d){
                        return colsWidth
                    })
                    .attr('fill',d=>colorTitleEntities(d.nbItem))
                    .style('cursor','pointer')
                    .on('click',listItem)
                // .attr("transform", "translate(" + 0 + ", " + 10 + ")")
                tTitle.append('text')
                    .text(function(d){
                        return d.tableName
                    })
                    .attr('dx',function(){
                        return colsWidth/2
                    })
                    .attr('dy',tableTitleHeight/2)
                    .attr('class','svg-text')
                    .attr('text-anchor','middle')
                    .attr('dominant-baseline','middle')
                    .attr('fill','#000')
                    .style('cursor','pointer')
                    .on('click',listItem)
            }

            // afficher list contenus de class
            function listItem(d){
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: me.site_url + "/page/ajaxPos?json=1",
                    data: {
                        'itemSet': d.id,
                        'action': 'listItem'
                    },
                    beforeSend: function(){
                        // afficher loading
                        $(".se-pre-con").show();
                    }
                })
                .done(function(data) {
                    // Cacher loading
                    $(".se-pre-con").hide();

                    var parsed = JSON.parse(data.success);
                    var str_content = "<table>";
                    $.each(parsed, function (key, val) {
                        str_content += "<tr><td><a href=\"#myModal\" data-toggle=\"modal\" data-id=\""+val.id+"\" data-name_entite=\""+d.tableName+"\" class=\"btn-block\">"+val.title+"</a></td></tr>";
                    });
                    str_content += "</table>";

                    var str_title = parsed.length + (parsed.length > 1 ? ' items' : ' item') + ' de la classe: ' + d.tableName;

                    me.show_model(str_title, str_content, 1);
                    
                    $("#modify").html(me.tab_modify(d.tableName));
                })
                .fail(function(e) {
                    //console.log("error = "+JSON.stringify(e))
                    alert("Une erreur s'est produite")
                });
            }

            //Créer un champ g
            function addColsWrap(){
                addTablesData.append('g')
                    .attr('class','table-cols')
                    .attr('transform',"translate(" + 0 + ", " + tableTitleHeight + ")")

                addColsGs()
            }
            function addColsGs(){
                addColsGsData = addTablesData.select('.table-cols')
                    .selectAll('g')
                    .data(function(d){
                        return d.cols
                    })
                    .enter()
                    .append('g')
                    .attr("transform",(d,i)=>`translate(${0},${i*colsHeight})`)
                    .attr('id',function(d){ return d.id })

                allColsGs = addTablesData.select('.table-cols')
                    .selectAll('g')
                    .data(function(d){
                        return d.cols
                    })

                colsAdd()
            }
            function addExtColsGs(obj){
                addColsGsData = d3.select('#'+obj.id).select('.table-cols')
                    .selectAll('g')
                    .data(function(d){
                        return d.cols
                    })
                    .enter()
                    .append('g')
                    .attr("transform",(d,i)=>`translate(${0},${i*colsHeight})`)
                    .attr('id',function(d){ return d.id })
                colsAdd()
            }
            function delColsGs(){
                allColsGs = allTables.select('.table-cols')
                    .selectAll('g')
                    .data(function(d){
                        return d.cols
                    })
                allColsGs.exit().remove()
            }
            //Ajouter des informations de champ
            function colsAdd(){
                addColsGsData.append('rect')
                    .attr('height',function(d){
                        return colsHeight
                    })
                    .attr('width',function(d){
                        return colsWidth
                    })
                    .attr('fill',d=>colorProprietes(d.nbItemPro))
                    .style('cursor','pointer')
                    .on('click',valeurPro)

                addColsGsData.append('text')
                    .attr('class','svg-text')
                    .text(function(d){
                        return d.itemName
                    })
                    .attr('dx',function(){
                        // return colsWidth/2
                        return 10
                    })
                    .attr('dy',colsHeight/2)
                    .attr('dominant-baseline','middle')
                    .attr('fill','#000')
                    .style('cursor','pointer')
                    .on('click',valeurPro)

                //Ajouter un surlignage
                addColsGsData.on('mouseenter',function(d){
                    colsLight.call(this,d,1)
                })
                    .on('mouseleave',function(d){
                        colsLight.call(this,d,0)
                    })
            }

            // afficher valeur de propriétés
            function valeurPro(d){
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: me.site_url + "/page/ajaxPos?json=1",
                    data: {
                        'itemSet': [d.id_rt, d.id], // id resource template, id propriété
                        'action': 'valeurPro'
                    },
                    beforeSend: function(){
                        // afficher loading
                        $(".se-pre-con").show();
                    }
                })
                .done(function(data) {
                    // Cacher loading
                    $(".se-pre-con").hide();
                    
                    var parsed = JSON.parse(data.success);
                    var type_modal = 2;
                    var str_content = "<table>";
                    $.each(parsed, function (key, val) {
                        if ((d.id != 1) && (d.id != 10)) {
                            var str_value = val.v_p;
                            if (d.itemName == 'Description') {
                                str_value = str_value.slice(0, 30) + '...';
                            }
                            str_content += "<tr><td><a class=\"propriete\" data-bs-toggle=\"collapse\" data-id=\""+val.id+"\" data-title=\""+val.t_i+"\" aria-expanded=\"false\" aria-controls=\"pro"+val.id+"\" href=\"#pro" + val.id + "\" style=\"color: #920b0b;\">" + str_value + "</a></td></tr>";
                            str_content += "<tr><td style='border-bottom:0px;'><div class=\"collapse scroller\" id=\"pro" + val.id + "\">";

                            $.each(val.t_i, function (key1, val1) {
                                str_content += "<a href=\"#myModal\" data-toggle=\"modal\" data-id=\""+val1.id+"\" class=\"btn-block\" style=\"color: #0d6efd;\"> - "+val1.title+"</a><br/>";
                            });
                            
                            str_content += "</div></td></tr>";
                        } else { 
                            var str_v = val.v_p; // cliquer sur propriete "Title"
                            var i_id = val.id;
                            if (d.id == 10) {// cliquer sur propriete "identifier"
                                $.each(val.t_i, function (key1, val1) {
                                    str_v = '.' + val1.title;
                                    i_id = val1.id;
                                });
                            } 
                            str_content += "<tr><td><a href=\"#myModal\" data-toggle=\"modal\" data-id=\""+i_id+"\" class=\"btn-block\">"+str_v+"</a></td></tr>";
                            type_modal = 1;
                            
                            $("#modify").html(me.tab_modify(d.tableName));
                        }
                    });
                    str_content += "</table>";

                    var str_title = parsed.length + (parsed.length > 1 ? ' valeurs' : ' valeur') + ' de propriétés: ' + d.itemName;

                    me.show_model(str_title, str_content, type_modal);
                })
                .fail(function(e) {
                    //console.log("error = "+JSON.stringify(e))
                    alert("Une erreur s'est produite")
                });
            }

            //Surbrillance du nœud
            function colsLight(data,isEnter){
                lineLight(data,isEnter)
                d3.select(this)
                    .select('rect')
                    .transition()
                    // .delay(50)
                    .attr('fill',isEnter ? colsStyle.fillLight : d=>colorProprietes(d.nbItemPro))
            }
            //Surbrillance de la ligne
            function lineLight(data,isEnter){
                d3.selectAll('.link-polyline')
                    //d3.selectAll('.link-line')
                    .attr('stroke',function(d){
                        let source = me.tables[d.source].cols[d.sourceIndex-1]
                        let target = me.tables[d.target].cols[d.targetIndex-1]
                        if(data.id === source.id || data.id === target.id ){
                            return isEnter ? lineStyle.strokeLight : colorLinks(d.nb)
                        }else{
                            return colorLinks(d.nb)
                        }
                    })
                    .attr('stroke-width',function(d){
                        let source = me.tables[d.source].cols[d.sourceIndex-1]
                        let target = me.tables[d.target].cols[d.targetIndex-1]
                        if(data.id === source.id || data.id === target.id ){
                            return isEnter ? lineStyle.strokeWidthLight : lineStyle.strokeWidthDefault
                        }else{
                            return lineStyle.strokeWidthDefault
                        }
                    })
            }
            //Élever le niveau du nœud
            function hoverLight(data,isEnter){
                // let preData = Object.assign(data)
                // if(isEnter){
                //   d3.select(this).raise()
                // }else{
                //   d3.select(this).lower()
                // }
            }
            //Bouton Ajouter
            function addFooter(){
                addFooterData = addTablesData.append('g')
                    .attr('class','table-foot')
                /*
                addFooterData.append('rect')
                    .attr('width',colsWidth)
                    .attr('height',tableFootHeight)
                    .attr('fill','#f8f8f9')
                */

                addFooterData.append('text')
                    .attr('class','table-foot-btn table-foot-btn-left')
                    .attr('dy',tableFootHeight/2)
                    .attr('dx',10)
                    .on('click',addCols)

                addFooterData.append('text')
                    .attr('class','table-foot-btn table-foot-btn-right')
                    .attr('dy',tableFootHeight/2)
                    .attr('dx',colsWidth-60)
                    .on('click',addNode)

                allFooter = allTables.selectAll('.table-foot')
                updateFoot()
            }
            function updateFoot(){
                allFooter = allTables.selectAll('.table-foot')
                allFooter.attr('transform',function(d){
                    return `translate(${0},${tableTitleHeight + colsHeight*d.cols.length})`
                })
                allFooter.selectAll('.table-foot-btn-left').text(function(d){
                    return d.text ? d.text[0] || btnText.leftOpen : btnText.leftOpen
                })
                allFooter.selectAll('.table-foot-btn-right').text(function(d){
                    return d.text ? d.text[1] || btnText.rightOpen : btnText.rightOpen
                })
            }
            return {
                changePort,
                svgAddNode,
                addExtColsGs,
                updateFoot,
                delColsGs,
                delTables,
            }
        }

        function addNode(data){
            let temp = me.tables.length
            let random = Math.floor(Math.random()*6)+1
            if(data.isOpen){ //Si le nœud est ouvert, la fonction en cours n'est pas terminée
                data.isOpen = false
                // tables = tables.filter(v=>v.id != data.id)
                console.table(me.tables)
                // links = links.filter(v=>v.source.id != data.id && v.target.id != data.id)
                // initObj.delTables()
                initObj.svgAddNode()
            }else{
                me.tables.push({
                    tableName:'sda_fdsf'+temp,
                    id:'table'+temp,
                    cols:new Array(random).fill(1).map((v2,i2)=>{
                        return {
                            id:'table'+temp+'cols'+i2,
                            itemName:'table'+temp+'colsName'+i2,
                        }
                    }),
                })
                me.links.push({
                    source:data.id,
                    target:'table'+temp,
                    relation:'line1',
                    sourceIndex:1,
                    targetIndex:Math.floor(Math.random()*random)+1,
                    value:1,
                })
                data.isOpen = true
                initObj.svgAddNode()
            }
            console.log(me.tables)
            console.log(me.links)

        }

        function addCols(data){
            let len = data.cols.length
            let col = new Array(2).fill(1).map((v,i2)=>{
                return {
                    id: data.id+'cols'+(i2+len),
                    itemName:data.id+'colsName'+(i2+len),
                }
            })
            if(data.isAddCols){//Ranger
                data.isAddCols = false
                data.text = [btnText.leftOpen,btnText.rightOpen]
                data.allCols = data.cols
                data.cols = data.linkCols
                initObj.delColsGs()
            }else{//Se dérouler
                data.isAddCols = true
                data.text = [btnText.leftClose,btnText.rightClose]
                data.linkCols = data.cols.concat()
                data.cols = data.cols.concat(col)
                initObj.addExtColsGs(data)
            }
            initObj.updateFoot()
        }

        this.toImg = function toImg(){
            var serializer = new XMLSerializer();
            var source = '<?xml version="1.0" standalone="no"?>\r\n' + serializer.serializeToString(svg.node());
            var image = new Image;
            image.src = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(source);
            var canvas = document.createElement("canvas");
            canvas.width = 1000;
            canvas.height = 800;
            var context = canvas.getContext("2d");
            context.fillStyle = '#fff';//#PNG après l'enregistrement des paramètres fff est blanc
            context.fillRect(0, 0, 10000, 10000);
            image.onload = function() {
                context.drawImage(image, 0, 0);
                var a = document.createElement("a");
                a.download = "name.png";
                a.href = canvas.toDataURL("image/png");
                a.click();
            };
        }

        this.downloadSVG = function downloadSVG(){
            let fileName = 'svg';
            let content = document.getElementById('wrap').innerHTML;
            let aTag = document.createElement('a');
            let blob = new Blob([content]);
            aTag.download = fileName;
            aTag.href = URL.createObjectURL(blob);
            aTag.click();
            URL.revokeObjectURL(blob);
        }

        this.savePos = function savePos(){
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: me.site_url + "/page/ajaxPos?json=1",
                data: {
                    'itemSet': me.arr_pos,
                    'action': 'updatePosition'
                }
            })
                .done(function (data) {
                    me.arr_pos = '';

                    var str_title = 'Notification';
                    var str_content = "Enregistrer les données avec succès";

                    me.show_model(str_title, str_content);
                })
                .fail(function (e) {
                    //console.log("error = " + JSON.stringify(e))
                    alert("Une erreur s'est produite lors de l'enregistrement")
                });
        }

        this.func_arr_pos = function func_arr_pos(arr) {
            me.arr_pos = arr
        }

        this.show_model = function show_model(title, content, type=false) {
            var id_modal = 'postModal';
            var id_modal_body_list = 'modal-body';
            if ((type == 1) || (type == 2)) {
                id_modal = 'postModalTabs';
                id_modal_body_list = 'modal-body-list';
            }

            $('#'+id_modal+' h5#postModalLabel').text(title);
            $('#'+id_modal+' .'+id_modal_body_list).html(content);

            var postModal = new bootstrap.Modal(document.getElementById(id_modal));
            postModal.show();
            
            if (type == 2) { // proprietes
                // masquer le onglet verifier
                document.getElementById("modify").style.display = 'none';
                document.getElementById("modify-tab").style.display = 'none';
                
                $('#view-tab').tab('show'); // l'onglet VOIR est selectionne
            }
            
        }
        
        this.hide_model = function hide_model(id_modal) {
            setTimeout(function() {
                $('#'+id_modal).modal('hide');
            },  5000);
        }

        $(document).ready(function() {
            $('#postModalTabs').on('shown.bs.modal', function (e) {
                // cliquer propriete
                $('[data-bs-toggle="collapse"]').click(function(e) {
                    e.preventDefault();
                });
                
                // cliquer entite
                $('[data-toggle="modal"]').click(function(e) {
                    e.preventDefault();

                    var id = $(this).data('id');
                    var name_entite = $(this).data('name_entite');

                    $("#view").html(me.tab_view(id));
                    $("#edit").html(me.tab_edit(id));
                });
                
                
            });
        });

        this.tab_view = function tab_view(id) {
            var str_view = '<div class="ratio ratio-1x1">';
            str_view += '<iframe src="'+me.site_url+'/item/'+id+'" title="" allowfullscreen></iframe>';
            str_view += '</div>';

            return str_view;
        }

        this.tab_edit = function tab_edit(id) {
            var str_edit = '';
            if (me.user_id > 0) {
                str_edit = '<div class="ratio ratio-1x1">';
                str_edit += '<iframe src="/admin/item/'+id+'" title="" allowfullscreen></iframe>';
                str_edit += '</div>';
            } else {
                str_edit = '<div style="padding-top: 20px;">';
                str_edit += 'Vous devez être connecté pour exécuter cette fonction.';
                str_edit += '</div>';
            }
            return str_edit;
        }

        this.tab_modify = function tab_modify(name_entite) {
            var str_view = '<div class="ratio ratio-1x1">';
            str_view += '<iframe src="'+me.site_url+'/page/cmap?name_entite='+name_entite+'" title="" allowfullscreen></iframe>';
            str_view += '</div>';

            return str_view;
        }

    }
}
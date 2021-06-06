class cmap {
    constructor(params) {
        var me = this;
        this.width = params.width ? params.width : 1200;
        this.height = params.height ? params.height : 800;
        this.tables = params.tables
        this.links = params.links
        this.arr_pos = []

        let svg = d3.select('svg')
            .attr('width',me.width)
            .attr('height',me.height)
        // .attr('viewBox',`-${width/2} -${height/2} ${width} ${height}`)


        let colsHeight = 30;//Hauteur du champ
        let tableTitleHeight = 30;//Hauteur du champ
        let tableFootHeight = 30;//Hauteur du champ
        let colsWidth = 200;
        let offLine = 20;//Décalage de nœud

        let btnText = {
            leftOpen:'',//'Extension de champ',
            leftClose:'',//'Réduire le champ',
            rightOpen:'',//'Élargissement des relations',
            rightClose:'',//'Effondrement de la relation',
        }

        //Définition du style
        let lineStyle = {
            strokeDefault:'#80848f',
            strokeWidthDefault:'2',
            strokeLight:'#19be6b',
            strokeWidthLight:'3',
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

            //Initialiser la disposition dirigée par la force
            let forceSimulation = d3.forceSimulation()
                .force('charge',d3.forceManyBody().strength(-100))//Force de charge
                .force('forceCollide',d3.forceCollide().radius(colsWidth/2+50))//Détection de collision
                .force('link',d3.forceLink().id(function(d){return d.id}))//link
            initSVG()
            //Première exécution
            function initSVG(){
                let zoom = d3.zoom()
                    .scaleExtent([0.1,0.6]) // normal [0.1,10]
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
                // svg.transition().delay(500).attr('viewBox',`${gPosition.x-20} ${gPosition.y-20} ${width} ${height}`)
                g.transition().delay(0).attr('transform',function(){
                    return `translate(${gPosition.x},${gPosition.y}) scale(0.6)` // normal scale(1)
                })
            }


            //dessiner

            let allTables, addTablesData, allLinks, addLinksData, allColsGs, addColsGsData,allFooter, addFooterData;

            function svgAddNode(){
                forceInit(me.tables,me.links)
                addTables()
                addLinks()
                initDrag()
            }

            function tick(){//force Fonction d'exécution itérative
                allTables.attr("transform", function(d){
                    return `translate(${d.x},${d.y})`
                })
                allLinks.attr('points',linkFn)
            }
            function end(){//force fonction de fin

            }
            function forceInit(nodes,links){//Ajouter des attributs d'emplacement aux objets d'origine des nœuds et des liens
                forceSimulation.nodes(nodes)
                    .on('end',end)
                    .on('tick',tick);
                forceSimulation.force('link')
                    .links(me.links)
                    .distance(function (d) {
                        return d.value * colsWidth + 100
                    })
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
                    .on('start',startFn)
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
                if(d.source.x < d.target.x){
                    res[2] = [d.target.x - offLine, d.target.y]
                    res[3] = [d.target.x, d.target.y]
                    if((d.source.x + colsWidth + offLine*0) < d.target.x ){
                        res[0] = [d.source.x + colsWidth, d.source.y]
                        res[1] = [d.source.x + colsWidth + offLine, d.source.y]
                    }else{
                        res[0] = [d.source.x, d.source.y]
                        res[1] = [d.source.x - offLine, d.source.y]
                    }
                }else{
                    res[1] = [d.source.x - offLine, d.source.y]
                    res[0] = [d.source.x, d.source.y]
                    if((d.target.x + colsWidth + offLine*0) < d.source.x ){
                        res[3] = [d.target.x + colsWidth, d.target.y]
                        res[2] = [d.target.x + colsWidth + offLine, d.target.y]
                    }else{
                        res[3] = [d.target.x, d.target.y]
                        res[2] = [d.target.x  - offLine, d.target.y]
                    }
                }
                res[0][1] += d.sourceIndex*colsHeight + colsHeight/2
                res[1][1] += d.sourceIndex*colsHeight + colsHeight/2
                res[2][1] += d.targetIndex*colsHeight + colsHeight/2
                res[3][1] += d.targetIndex*colsHeight + colsHeight/2
                return res.map(v=>v.join(',')).join(' ')
            }
            function startFn(d){//Faites glisser le début
                if(!d3.event.active){
                    forceSimulation.alphaTarget(0.8).restart()//[0,1]
                }
                d.fx = d.x
                d.fy = d.y
            }
            function dragFn(d){//Faire glisser
                d.fx = d3.event.x
                d.fy = d3.event.y
            }
            function endFn(d){//Faites glisser la fin
                if(!d3.event.active){
                    forceSimulation.alphaTarget(0)
                }

                // Ajouter: position après glissement
                me.arr_pos[d.id] = [d.fx, d.fy]
                me.func_arr_pos(me.arr_pos)

                // d.fx = null
                // d.fy = null
            }
            //Ajouter une connexion
            function addLinks(){
                addLinksData = d3.select('.links')
                    .selectAll("polyline")
                    .data(me.links)
                    .enter()
                    .append("polyline")
                    .attr("fill",'none')
                    .attr("stroke",'#000')
                    .attr("stroke-width",2)
                    .attr('class','link-polyline')

                //arrow
                svg.append("svg:defs").append("svg:marker")
                    .attr("id", "arrow")
                    .attr("refX", 6)
                    .attr("refY", 6)
                    .attr("markerWidth", 30)
                    .attr("markerHeight", 30)
                    .attr("markerUnits","userSpaceOnUse")
                    .attr("orient", "auto")
                    .append("path")
                    .attr("d", "M 0 0 12 6 0 12 3 6")
                    .style("fill", "black");

                allLinks =  d3.select('.links')
                    .selectAll('polyline')
                    .data(me.links)
                    .attr("marker-end", "url(#arrow)")
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
                    .attr('fill','#FBE17E')
                    .style('cursor','pointer')
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
                    .attr('fill','#C5D2E7')
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

                //Ajouter un surlignage
                addColsGsData.on('mouseenter',function(d){
                    colsLight.call(this,d,1)
                })
                    .on('mouseleave',function(d){
                        colsLight.call(this,d,0)
                    })
            }
            //Surbrillance du nœud
            function colsLight(data,isEnter){
                lineLight(data,isEnter)
                d3.select(this)
                    .select('rect')
                    .transition()
                    // .delay(50)
                    .attr('fill',isEnter ? colsStyle.fillLight : colsStyle.fillDefalt)

            }
            //Surbrillance de la ligne
            function lineLight(data,isEnter){
                d3.selectAll('.link-polyline')
                    .attr('stroke',function(d){
                        let source = d.source.cols[d.sourceIndex-1]
                        let target = d.target.cols[d.targetIndex-1]
                        if(data.id === source.id || data.id === target.id ){
                            return isEnter ? lineStyle.strokeLight : lineStyle.strokeDefault
                        }else{
                            return lineStyle.strokeDefault
                        }
                    })
                    .attr('stroke-width',function(d){
                        let source = d.source.cols[d.sourceIndex-1]
                        let target = d.target.cols[d.targetIndex-1]
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
            // console.log()
            let fileName = 'svg';
            let content = document.getElementById('wrap').innerHTML;
            let aTag = document.createElement('a');
            let blob = new Blob([content]);
            aTag.download = fileName;
            aTag.href = URL.createObjectURL(blob);
            aTag.click();
            URL.revokeObjectURL(blob);
        }

        this.func_arr_pos = function func_arr_pos(arr){
            me.arr_pos = arr
            console.log("\n")
            console.log(me.arr_pos)
        }

    }
}




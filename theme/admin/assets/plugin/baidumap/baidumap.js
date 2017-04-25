
var BaiduMap = {

  map : null,

  /* 配置 */
  options : {
    mapDiv           : 'container',      //div对象ID
    mapLevel         : 10,               //地图缩放等级
    mapCenter        : '上海',           //地图中心点 坐标or城市名
    enableWheel      : true,             //是否鼠标滚轮
    enableKeyboard   : false,            //是否开启键盘
    enableInertial   : true,             //惯性拖拽
    enableNavigation : true,            //鱼骨头
    navType          : BMAP_NAVIGATION_CONTROL_SMALL,  //鱼骨头样式
    mapType          : BMAP_NORMAL_MAP                 //地图视图 BMAP_HYBRID_MAP
  },

  /* 插件 */
  plugins : {},

  /* 标注点 */
  markers : {},
  
  /* 单标记点 */
  marker : null,
  point : null,


  /* 公共方法 */

  //设置配置
  setOptions : function(options) {
    return this._set(options, this.options);
  },

  //初始化地图
  init : function(options) {
    var options = this.setOptions( options );
    this.map  = new BMap.Map( options.mapDiv, {mapType:options.mapType} );
    if ( this._isPoint(options.mapCenter) ) {
      var point = new BMap.Point( options.mapCenter.split(',')[0], options.mapCenter.split(',')[1] );
      this.map.centerAndZoom( point, options.mapLevel );
    }
    else {
      this.map.centerAndZoom( options.mapCenter, options.mapLevel );
    }
    if ( options.enableWheel ) this.map.enableScrollWheelZoom();
    if ( options.enableKeyboard ) this.map.enableKeyboard();
    if ( options.enableInertial ) this.map.enableInertialDragging();
    if ( options.enableNavigation ) this.map.addControl( new BMap.NavigationControl({type:options.navType}) );
    return this.map;
  },


  //调用插件
  callPlugin : function(plug, opt) {
    switch(plug){
      case 'marker' :
        return new BMapLib.MarkerTool(this.map, opt || {autoClose:true,followText:'添加标注'});
        break;
      case 'distance' :
        if ( !this.plugins.distance )
          this.plugins.distance = new BMapLib.DistanceTool(this.map, opt || {});
        return this.plugins.distance;
        break;
    }
  },


  //添加标注
  createMarker : function(point, html, opts, evts) {

    if ( this.markers[point] ) {
      this.markers[point].show();
      return;
    }

    var options = {
      icon   : null,          //自定义图标
      width  : 20,            //图标显示宽度
      height : 20,            //图标显示高度
      offx   : 0,             //图标X偏移
      offx   : 0,             //图标Y偏移
      iconx  : 0,             //背景x偏移
      icony  : 0,             //背景y偏移
      enableDragging : false, //鼠标拖动
      animation : false       //跳动
    };
    var events = {
      /*
      click
      dbclick
      mouseover
      mouseout
      dragstart
      dragend
      dragging
      rightclick
      */
    };
    this._set(opts, options);
    this._set(evts, events);

    var p = new BMap.Point(point.split(',')[0], point.split(',')[1]);
    if( options.icon ) {
      var icon = new BMap.Icon(options.icon, new BMap.Size(options.width, options.height), {
          anchor: new BMap.Size(options.offx, options.offy),
          imageOffset: new BMap.Size(options.iconx, options.icony)
        });
      var marker = new BMap.Marker(p, {icon: icon});
    }
    else {
      var marker = new BMap.Marker(p);
    }
    if( options.enableDragging ) marker.enableDragging(true);
    if( options.animation ) marker.setAnimation(BMAP_ANIMATION_BOUNCE);

    for(var evt in events) {
      marker.addEventListener(evt, events[evt]);
    }

    this.map.addOverlay(marker);

    this.markers[point] = marker;
  },

  //删除标注
  removeMarker : function(point){
    if ( this.markers[point] ) {
      this.map.removeOverlay( this.markers[point] );
      this.markers[point] = null;
    }
  },

  //隐藏标注
  hideMarker : function(point){
    this.markers[point] && this.markers[point].hide();
  },

  //显示标注
  showMarker : function(point){
    this.markers[point] && this.markers[point].show();
  },

  //清除所有覆盖物
  clearMarkers : function() {
    this.map.clearOverlays();
    this.markers = {};
  },

  //开启描点
  startMarker : function(){
    this.point = new BMap.Point(121.487899, 31.249162);
    this.marker = new BMap.Marker(this.point);
    this.marker.enableDragging();    
    this.marker.addEventListener('click', function(e){
      point_draw(e.target.getPosition().lng, e.target.getPosition().lat);
    });
    this.marker.addEventListener('dragend', function(e){
      point_draw(e.point.lng, e.point.lat);
    });
    this.map.addOverlay(this.marker);
  },

  //创建信息窗口
  createInfoWindow : function(html, opts) {
    var options = {
      width     : 150,
      height    : 60,
      maxWidth  : 700,
      title     : ''
    };
    this._set(opts, options);
    return new BMap.InfoWindow(html, options);
  },


  //创建右键菜单
  createContextMenu : function(table) {
    var contextMenu = new BMap.ContextMenu();
    for (var i=0; i < table.length; i++) {
      contextMenu.addItem(new BMap.MenuItem(table[i].text, table[i].callback, 100));
      table[i].sep && contextMenu.addSeparator();
    }
    this.map.addContextMenu(contextMenu);
  },


  //获取数据
  getDatas : function(url, opt) {

  },

  //显示数据
  showDatas : function(url, opt) {

  },

  //搜索
  search : function(keys,key2) {
    var This = this;
    var search = new BMap.LocalSearch(keys, {
      onSearchComplete: function (results){
        if (results && results.getNumPois()) {
          var points = [];
          for (var i=0; i<results.getCurrentNumPois(); i++) {
            points.push(results.getPoi(i).point);
          }
          if (points.length > 1) {
            This.map.setViewport(points);
          }
          else {
            This.map.centerAndZoom(points[0], 11);
          }
          This.point = This.map.getCenter();
          This.marker.setPosition(This.point);
          point_draw(This.point.lng,This.point.lat);
        }
        else {
          alert('无法定位到该地址！');
        }
      }
    });
    search.search(key2 || keys);
  },



  /* 其它方法 */
  
  //默认右键菜单: 集成测距和卯点
  default_contextmenu : function() {
    var This = this;
    var table = [
      /*
      {
        text:'测量距离',
        callback:function(){
          This.callPlugin('distance').open();
        } 
      },
      */
      {
        text:'自主卯点',
        callback:function(p){
          var marker = This.callPlugin('marker');          
          marker.addEventListener('markend', function(e){
            var m = e.marker;            
            var html = '<input type="hidden" id="xx" name="xx" value="'+m.getPosition().lng+'"/>'+
              '<input type="hidden" id="yy" name="yy" value="'+m.getPosition().lat+'"/>' +
              '<a href="###" onclick="point_ok();" style="font-size:14px;">确定此点</a><br>' + 
              '';
            point_draw(m.getPosition().lng,m.getPosition().lat);
            var infowindow = This.createInfoWindow(html,{enableCloseOnClick:false,offset:new BMap.Size(0, -22)});
            m.enableDragging();
            m.openInfoWindow(infowindow);
            m.addEventListener('click', function(ee){
              ee.target.openInfoWindow(infowindow);
              document.getElementById('xx').value = ee.point.lng;
              document.getElementById('yy').value = ee.point.lat;
              point_draw(ee.point.lng,ee.point.lat);
            });
            m.addEventListener('dragend', function(ee){
              document.getElementById('xx').value = ee.point.lng;
              document.getElementById('yy').value = ee.point.lat;
              point_draw(ee.point.lng,ee.point.lat);
            });
            m.addEventListener('dblclick', function(ee){
              This.map.removeOverlay(ee.target);
            });
          });
          marker.open();
        },
        sep:true
      },
      {
        text:'放大',
        callback:function(){
          This.map.zoomIn();
        }
      },
      {
        text:'缩小',
        callback:function(){
          This.map.zoomOut();
        }
      }
    ];
    this.createContextMenu(table);
  },



  /* 私有方法 */

  _set : function(source, dest) {
    for(var key in source) {
      dest[key] = source[key];
    }
    return dest;
  },

  _isArray : function(v) {
    return v &&
           typeof(v)==='object' &&
           typeof(v.length)==='number' &&
           typeof(v.splice)==='function' &&
           !(v.propertyIsEnumerable('length'));
  },

  _isNumer : function(v) {
    return typeof(v)==='number' && isFinite(v);
  },

  _isPoint : function(v) {
    return v.indexOf(',') > 0;
  },
  
  _getPars : function(str,par){
    var reg = new RegExp(par+"=((\\d+|[.,])*)","g");
    return reg.exec(str)[1];
  }


}


function marker_tilesloaded() {
    BaiduMap.marker.setPosition(BaiduMap.map.getCenter());
    BaiduMap.map.removeEventListener('tilesloaded',marker_tilesloaded);
}
function marker_moveend() {
    BaiduMap.marker.setPosition(BaiduMap.map.getCenter());
    BaiduMap.map.removeEventListener('moveend',marker_moveend);
}
function baidumap_toggle() {
    if($('#divbaidumap').css('left')=='59px') {
        $('#divbaidumap').css('left','-9999px'); 
    } else {
        $('#divbaidumap').css('left','59px'); 
    }
    baidumap_init();
}
function baidumap_init() {
    if($('#divbaidumap').css('left')!='59px') return;
    var sheng = $('.prov').val();
    var djs = $('.city').val();
    var lng = $('#lng').val();
    var lat = $('#lat').val();
    var mapcity = $('#mapcity').val();
    if (!mapcity) {
        $('#mapcity').val(sheng);
    }
    if (lng && lat) {
        BaiduMap.map.centerAndZoom(new BMap.Point(lng,lat),14);
        BaiduMap.marker.setPosition(new BMap.Point(lng,lat));
    } else if (djs) {
        BaiduMap.map.setCenter(djs);
        BaiduMap.map.setZoom(12);
        BaiduMap.map.addEventListener('tilesloaded', marker_tilesloaded);
    } else {
        BaiduMap.map.setCenter(sheng);
        BaiduMap.map.setZoom(10);
        BaiduMap.map.addEventListener('tilesloaded', marker_tilesloaded);
    }
}
function point_draw(lng,lat){
    $('#lng').val(lng);
    $('#lat').val(lat);
}

$(function(){
    BaiduMap.init();
    BaiduMap.startMarker();
    //$('#divbaidumap').bgiframe();
    document.getElementById('mapaddr').onkeydown = function (evt){
        evt = evt || event;
        if (evt.keyCode == 13) {
          BaiduMap.search($('#mapcity').val(),$('#mapaddr').val());
        }
    };
});
